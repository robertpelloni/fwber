import { Server, Socket } from 'socket.io';
import { createServer } from 'http';
import jwt from 'jsonwebtoken';
import prisma from './lib/prisma.js';

interface AuthenticatedSocket extends Socket {
  user?: {
    id: number;
    name: string;
  };
}

export function setupSocketIO(httpServer: any) {
  const io = new Server(httpServer, {
    cors: {
      origin: process.env.FRONTEND_URL || '*',
      methods: ['GET', 'POST'],
      credentials: true,
    },
  });

  // Authentication Middleware
  io.use(async (socket: AuthenticatedSocket, next) => {
    const token = socket.handshake.auth.token;

    if (!token) {
      return next(new Error('Authentication error: No token provided'));
    }

    try {
      const decoded = jwt.verify(token, process.env.JWT_SECRET || 'secret') as any;
      const user = await prisma.user.findUnique({
        where: { id: decoded.userId },
        select: { id: true, name: true },
      });

      if (!user) {
        return next(new Error('Authentication error: User not found'));
      }

      socket.user = user;
      next();
    } catch (err) {
      next(new Error('Authentication error: Invalid token'));
    }
  });

  io.on('connection', (socket: AuthenticatedSocket) => {
    console.log(`[Socket.io] User connected: ${socket.user?.name} (ID: ${socket.user?.id})`);

    // Join user-specific room for private messages
    if (socket.user) {
      socket.join(`user:${socket.user.id}`);
    }

    // Handle real-time messaging
    socket.on('send_message', async (data: { receiverId: number; content: string }) => {
      if (!socket.user) return;

      try {
        const message = await prisma.message.create({
          data: {
            sender_id: socket.user.id,
            receiver_id: data.receiverId,
            content: data.content,
          },
        });

        // Emit to receiver's private room
        io.to(`user:${data.receiverId}`).emit('new_message', {
          id: message.id,
          senderId: socket.user.id,
          content: message.content,
          createdAt: message.created_at,
        });

        // Ack to sender
        socket.emit('message_sent', { id: message.id });
      } catch (err) {
        socket.emit('error', { message: 'Failed to send message' });
      }
    });

    // Handle Typing status
    socket.on('typing', (data: { receiverId: number }) => {
      if (!socket.user) return;
      io.to(`user:${data.receiverId}`).emit('user_typing', { senderId: socket.user.id });
    });

    socket.on('disconnect', () => {
      console.log(`[Socket.io] User disconnected: ${socket.user?.id}`);
    });
  });

  return io;
}
