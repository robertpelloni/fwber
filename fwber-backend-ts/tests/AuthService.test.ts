import { jest } from '@jest/globals';
import bcrypt from 'bcryptjs';

// Setup mock objects and functions
const mockCreate = jest.fn();
const mockFindUnique = jest.fn();
const mockFindFirst = jest.fn();
const mockUpdate = jest.fn();
const mockCount = jest.fn();
const mockUpsert = jest.fn();
const mockDeleteMany = jest.fn();
const mockDelete = jest.fn().mockResolvedValue({}); // Must resolve a promise so .catch() works

const mockPrisma = {
  users: {
    findUnique: mockFindUnique,
    findFirst: mockFindFirst,
    create: mockCreate,
    update: mockUpdate,
    count: mockCount,
  },
  user_profiles: {
    create: jest.fn(),
  },
  wallet_transactions: {
    create: jest.fn(),
  },
  notification_preferences: {
    createMany: jest.fn(),
  },
  password_reset_tokens: {
    upsert: mockUpsert,
    deleteMany: mockDeleteMany,
    delete: mockDelete,
    findFirst: mockFindFirst,
  }
};

const mockSendVerificationEmail = jest.fn();
const mockSendPasswordResetEmail = jest.fn();
const mockCreateVerificationToken = jest.fn();

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: mockPrisma,
}));

jest.unstable_mockModule('../src/lib/email.js', () => ({
  sendVerificationEmail: mockSendVerificationEmail,
  sendPasswordResetEmail: mockSendPasswordResetEmail,
}));

jest.unstable_mockModule('../src/lib/verification-store.js', () => ({
  createVerificationToken: mockCreateVerificationToken,
}));

// We must dynamically import the service *after* the mock is established
const { AuthService } = await import('../src/services/AuthService.js');

describe('AuthService', () => {
  let authService: AuthService;

  beforeEach(() => {
    jest.clearAllMocks();
    authService = new AuthService();
  });

  describe('loginUser', () => {
    const testUser = {
      email: 'test@example.com',
      password: 'password123',
    };

    it('should throw "Invalid credentials" if user is not found', async () => {
      mockFindUnique.mockResolvedValueOnce(null);

      await expect(authService.loginUser(testUser)).rejects.toThrow('Invalid credentials');
    });

    it('should throw "Invalid credentials" if password does not match', async () => {
      const hashedPassword = await bcrypt.hash('differentpassword', 10);
      mockFindUnique.mockResolvedValueOnce({
        id: 1n,
        email: testUser.email,
        password: hashedPassword,
        decoy_password: null,
      });

      await expect(authService.loginUser(testUser)).rejects.toThrow('Invalid credentials');
    });

    it('should login successfully with correct password', async () => {
      const hashedPassword = await bcrypt.hash(testUser.password, 10);
      mockFindUnique.mockResolvedValueOnce({
        id: 1n,
        email: testUser.email,
        password: hashedPassword,
        decoy_password: null,
      });
      mockCount.mockResolvedValueOnce(0); // For hydrateUser referrals count

      const result = await authService.loginUser(testUser);
      expect(result).toHaveProperty('token');
      expect(result.user.email).toBe(testUser.email);
    });

    it('should login with decoy password and return decoy user', async () => {
      const actualPassword = await bcrypt.hash('actualpassword', 10);
      const decoyPasswordHash = await bcrypt.hash(testUser.password, 10);

      mockFindUnique.mockResolvedValueOnce({
        id: 1n,
        email: testUser.email,
        password: actualPassword,
        decoy_password: decoyPasswordHash,
        decoy_user_id: 2n
      }); // Main User fetch

      mockFindUnique.mockResolvedValueOnce({
        id: 2n,
        email: 'decoy@example.com',
      }); // Decoy User fetch
      mockCount.mockResolvedValueOnce(0);

      const result = await authService.loginUser(testUser);
      expect(result).toHaveProperty('token');
      expect(result.user.email).toBe('decoy@example.com');
    });
  });

  describe('registerUser', () => {
    const newUser = {
      name: 'New User',
      email: 'newuser@example.com',
      password: 'password123',
    };

    it('should throw "User already exists" if user is found', async () => {
      mockFindUnique.mockResolvedValueOnce({
        id: 1n,
        email: newUser.email,
      });

      await expect(authService.registerUser(newUser)).rejects.toThrow('User already exists');
    });

    it('should register successfully, reward referral, and return a token', async () => {
      const newUserWithReferral = { ...newUser, referral_code: 'XYZ123' };

      mockFindUnique.mockResolvedValueOnce(null); // No existing user
      mockFindFirst.mockResolvedValueOnce({ id: 5n }); // Referrer found

      mockCreate.mockResolvedValueOnce({
        id: 2n,
        name: newUserWithReferral.name,
        email: newUserWithReferral.email,
      }); // User created

      mockFindUnique.mockResolvedValueOnce({
         id: 2n,
         name: newUserWithReferral.name,
         email: newUserWithReferral.email,
      }); // Fresh user fetch

      mockCount.mockResolvedValueOnce(0); // referrals count

      mockCreateVerificationToken.mockResolvedValueOnce('verify-token-123');

      const result = await authService.registerUser(newUserWithReferral);

      expect(result).toHaveProperty('token');
      expect(result.user.email).toBe(newUserWithReferral.email);
      expect(mockUpdate).toHaveBeenCalledWith(expect.objectContaining({
        where: { id: 5n },
        data: { token_balance: { increment: 50 } }
      }));
      expect(mockSendVerificationEmail).toHaveBeenCalledWith(newUserWithReferral.email, 'verify-token-123');
    });
  });

  describe('requestPasswordReset', () => {
    it('should silently return if email is not found', async () => {
      mockFindUnique.mockResolvedValueOnce(null);
      await authService.requestPasswordReset('notfound@example.com');
      expect(mockUpsert).not.toHaveBeenCalled();
    });

    it('should upsert token and send email if user is found', async () => {
      mockDeleteMany.mockResolvedValueOnce({});
      mockFindUnique.mockResolvedValueOnce({ id: 1n, email: 'found@example.com' });
      await authService.requestPasswordReset('found@example.com');
      expect(mockUpsert).toHaveBeenCalled();
      expect(mockSendPasswordResetEmail).toHaveBeenCalled();
    });
  });

  describe('executePasswordReset', () => {
    it('should throw if token is invalid or expired', async () => {
      mockFindFirst.mockResolvedValueOnce(null);
      await expect(authService.executePasswordReset({ token: 'invalid', password: 'new' })).rejects.toThrow('Invalid or expired reset token');

      mockFindFirst.mockResolvedValueOnce({ expires_at: new Date(Date.now() - 10000) }); // Expired
      await expect(authService.executePasswordReset({ token: 'expired', password: 'new' })).rejects.toThrow('Invalid or expired reset token');
    });

    it('should update password and delete token on success', async () => {
      mockFindFirst.mockResolvedValueOnce({
        user_id: 1n,
        email: 'test@example.com',
        expires_at: new Date(Date.now() + 10000)
      });

      await authService.executePasswordReset({ token: 'valid', password: 'newpassword123' });

      expect(mockUpdate).toHaveBeenCalledWith(expect.objectContaining({
        where: { id: 1n }
      }));
      expect(mockDelete).toHaveBeenCalled();
    });
  });
});
