# Proximity Chatroom Frontend Implementation - Complete Guide

## React Components Architecture

### Page Components

#### Proximity Chatrooms Discovery Page
```tsx
// app/proximity-chatrooms/page.tsx
export default function ProximityChatroomsPage() {
  const { user, isAuthenticated } = useAuth();
  const [location, setLocation] = useState<LocationState>({
    latitude: null,
    longitude: null,
    error: null,
  });
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedType, setSelectedType] = useState<string>('all');
  const [radius, setRadius] = useState(1000);
  const [showCreateForm, setShowCreateForm] = useState(false);

  // Get user's current location
  useEffect(() => {
    if (!navigator.geolocation) {
      setLocation(prev => ({ ...prev, error: 'Geolocation is not supported by this browser.' }));
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        setLocation({
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          error: null,
        });
      },
      (error) => {
        setLocation(prev => ({
          ...prev,
          error: `Error getting location: ${error.message}`,
        }));
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 300000, // 5 minutes
      }
    );
  }, []);

  // Find nearby proximity chatrooms
  const { data: nearbyChatrooms, isLoading, error } = useNearbyProximityChatrooms({
    latitude: location.latitude || 0,
    longitude: location.longitude || 0,
    radius_meters: radius,
    type: selectedType === 'all' ? undefined : selectedType,
    search: searchTerm || undefined,
  });

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header with location status */}
      {/* Filters for search, type, and radius */}
      {/* Chatrooms list with real-time updates */}
      {/* Create chatroom modal */}
    </div>
  );
}
```

#### Individual Proximity Chatroom Page
```tsx
// app/proximity-chatrooms/[id]/page.tsx
export default function ProximityChatroomPage({ params }: ProximityChatroomPageProps) {
  const { user, isAuthenticated } = useAuth();
  const chatroomId = parseInt(params.id);
  const [newMessage, setNewMessage] = useState('');
  const [selectedTab, setSelectedTab] = useState<'all' | 'networking' | 'social'>('all');
  const [showMembers, setShowMembers] = useState(false);

  // Get user's current location
  const [location, setLocation] = useState<{ latitude: number | null; longitude: number | null }>({
    latitude: null,
    longitude: null,
  });

  // Hooks for data fetching
  const { data: chatroom, isLoading: chatroomLoading } = useProximityChatroom(
    chatroomId,
    location.latitude && location.longitude ? location : undefined
  );
  
  const { data: messages, isLoading: messagesLoading } = useProximityChatroomMessages(chatroomId, {
    type: selectedTab === 'all' ? undefined : selectedTab,
  });
  
  const { data: members, isLoading: membersLoading } = useProximityChatroomMembers(chatroomId);

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header with chatroom info */}
      {/* Message tabs (all, networking, social) */}
      {/* Messages list with real-time updates */}
      {/* Message input form */}
      {/* Sidebar with members and actions */}
    </div>
  );
}
```

### React Hooks Implementation

#### Custom Hooks for Data Management
```typescript
// lib/hooks/use-proximity-chatrooms.ts

// Find nearby proximity chatrooms
export function useNearbyProximityChatrooms(filters: FindNearbyRequest) {
  return useQuery({
    queryKey: proximityChatroomKeys.nearby(filters),
    queryFn: () => findNearby(filters),
    enabled: !!filters.latitude && !!filters.longitude,
    staleTime: 30 * 1000, // 30 seconds
  });
}

// Single proximity chatroom
export function useProximityChatroom(id: number, location?: { latitude: number; longitude: number }) {
  return useQuery({
    queryKey: proximityChatroomKeys.detail(id),
    queryFn: () => getProximityChatroom(id, location),
    enabled: !!id,
    staleTime: 2 * 60 * 1000, // 2 minutes
  });
}

// Proximity chatroom messages
export function useProximityChatroomMessages(chatroomId: number, filters: any = {}) {
  return useQuery({
    queryKey: proximityChatroomKeys.messages(chatroomId, filters),
    queryFn: () => getProximityChatroomMessages(chatroomId, filters),
    enabled: !!chatroomId,
    staleTime: 30 * 1000, // 30 seconds
  });
}

// Mutations
export function useCreateProximityChatroom() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: CreateProximityChatroomRequest) => createProximityChatroom(data),
    onSuccess: (newChatroom) => {
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.all });
      queryClient.setQueryData(proximityChatroomKeys.detail(newChatroom.id), newChatroom);
    },
  });
}

export function useSendProximityMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, data }: { chatroomId: number; data: SendProximityMessageRequest }) =>
      sendProximityMessage(chatroomId, data),
    onSuccess: (newMessage, { chatroomId }) => {
      queryClient.setQueryData(
        proximityChatroomKeys.messages(chatroomId, {}),
        (oldData: any) => {
          if (!oldData) return oldData;
          
          return {
            ...oldData,
            data: [newMessage, ...oldData.data],
            total: oldData.total + 1,
          };
        }
      );
      
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.detail(chatroomId) });
    },
  });
}
```

### API Client Implementation

#### TypeScript API Client
```typescript
// lib/api/proximity-chatrooms.ts

export interface ProximityChatroom {
  id: number;
  name: string;
  description: string;
  type: 'networking' | 'social' | 'dating' | 'professional' | 'casual';
  radius_meters: number;
  is_public: boolean;
  owner_id: number;
  member_count: number;
  distance_meters?: number;
  last_activity_at: string;
  created_at: string;
  updated_at: string;
  owner?: User;
}

export interface ProximityChatroomMessage {
  id: number;
  proximity_chatroom_id: number;
  user_id: number;
  content: string;
  type: 'general' | 'networking' | 'social' | 'professional';
  is_pinned: boolean;
  is_deleted: boolean;
  created_at: string;
  updated_at: string;
  user?: User;
  reactions?: MessageReaction[];
}

export interface FindNearbyRequest {
  latitude: number;
  longitude: number;
  radius_meters?: number;
  type?: string;
  search?: string;
}

export interface CreateProximityChatroomRequest {
  name: string;
  description: string;
  latitude: number;
  longitude: number;
  radius_meters?: number;
  type?: string;
  is_public?: boolean;
}

// API Functions
export const findNearby = async (filters: FindNearbyRequest): Promise<ProximityChatroom[]> => {
  const response = await apiClient.get('/proximity-chatrooms/find-nearby', {
    params: filters
  });
  return response.data.data;
};

export const createProximityChatroom = async (data: CreateProximityChatroomRequest): Promise<ProximityChatroom> => {
  const response = await apiClient.post('/proximity-chatrooms', data);
  return response.data;
};

export const getProximityChatroom = async (id: number, location?: { latitude: number; longitude: number }): Promise<ProximityChatroom> => {
  const params = location ? { latitude: location.latitude, longitude: location.longitude } : {};
  const response = await apiClient.get(`/proximity-chatrooms/${id}`, { params });
  return response.data;
};

export const joinProximityChatroom = async (id: number, data: JoinProximityChatroomRequest): Promise<any> => {
  const response = await apiClient.post(`/proximity-chatrooms/${id}/join`, data);
  return response.data;
};

export const leaveProximityChatroom = async (id: number): Promise<any> => {
  const response = await apiClient.post(`/proximity-chatrooms/${id}/leave`);
  return response.data;
};

export const sendProximityMessage = async (chatroomId: number, data: SendProximityMessageRequest): Promise<ProximityChatroomMessage> => {
  const response = await apiClient.post(`/proximity-chatrooms/${chatroomId}/messages`, data);
  return response.data;
};
```

### State Management

#### TanStack Query Configuration
```typescript
// lib/query-client.ts
import { QueryClient } from '@tanstack/react-query';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000, // 5 minutes
      cacheTime: 10 * 60 * 1000, // 10 minutes
      retry: 3,
      retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30000),
    },
    mutations: {
      retry: 1,
    },
  },
});
```

#### Query Keys
```typescript
// lib/hooks/use-proximity-chatrooms.ts
export const proximityChatroomKeys = {
  all: ['proximity-chatrooms'] as const,
  nearby: (filters: FindNearbyRequest) => [...proximityChatroomKeys.all, 'nearby', filters] as const,
  details: () => [...proximityChatroomKeys.all, 'detail'] as const,
  detail: (id: number) => [...proximityChatroomKeys.details(), id] as const,
  messages: (chatroomId: number, filters: any) => [...proximityChatroomKeys.detail(chatroomId), 'messages', filters] as const,
  members: (chatroomId: number) => [...proximityChatroomKeys.detail(chatroomId), 'members'] as const,
  networking: (chatroomId: number, filters: NearbyNetworkingRequest) => [...proximityChatroomKeys.detail(chatroomId), 'networking', filters] as const,
  analytics: (chatroomId: number) => [...proximityChatroomKeys.detail(chatroomId), 'analytics'] as const,
  pinned: (chatroomId: number) => [...proximityChatroomKeys.detail(chatroomId), 'pinned'] as const,
  networkingMessages: (chatroomId: number) => [...proximityChatroomKeys.detail(chatroomId), 'networking-messages'] as const,
  socialMessages: (chatroomId: number) => [...proximityChatroomKeys.detail(chatroomId), 'social-messages'] as const,
  replies: (chatroomId: number, messageId: number) => [...proximityChatroomKeys.detail(chatroomId), 'replies', messageId] as const,
};
```

### Real-time Features

#### WebSocket Integration
```typescript
// lib/hooks/use-websocket.ts
export function useWebSocket(url: string) {
  const [socket, setSocket] = useState<WebSocket | null>(null);
  const [isConnected, setIsConnected] = useState(false);
  const [lastMessage, setLastMessage] = useState<any>(null);

  useEffect(() => {
    const ws = new WebSocket(url);
    
    ws.onopen = () => {
      setIsConnected(true);
      setSocket(ws);
    };
    
    ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      setLastMessage(data);
    };
    
    ws.onclose = () => {
      setIsConnected(false);
      setSocket(null);
    };
    
    ws.onerror = (error) => {
      console.error('WebSocket error:', error);
    };

    return () => {
      ws.close();
    };
  }, [url]);

  const sendMessage = (message: any) => {
    if (socket && isConnected) {
      socket.send(JSON.stringify(message));
    }
  };

  return { socket, isConnected, lastMessage, sendMessage };
}
```

#### Real-time Message Updates
```typescript
// lib/hooks/use-proximity-chatrooms.ts
export function useProximityChatroomMessages(chatroomId: number, filters: any = {}) {
  const { lastMessage } = useWebSocket(`ws://localhost:8000/ws/proximity-chatrooms/${chatroomId}`);
  const queryClient = useQueryClient();

  useEffect(() => {
    if (lastMessage) {
      // Update cache with new message
      queryClient.setQueryData(
        proximityChatroomKeys.messages(chatroomId, filters),
        (oldData: any) => {
          if (!oldData) return oldData;
          
          return {
            ...oldData,
            data: [lastMessage, ...oldData.data],
            total: oldData.total + 1,
          };
        }
      );
    }
  }, [lastMessage, chatroomId, filters, queryClient]);

  return useQuery({
    queryKey: proximityChatroomKeys.messages(chatroomId, filters),
    queryFn: () => getProximityChatroomMessages(chatroomId, filters),
    enabled: !!chatroomId,
    staleTime: 30 * 1000, // 30 seconds
  });
}
```

### UI Components

#### Proximity Chatroom Card
```tsx
// components/ProximityChatroomCard.tsx
interface ProximityChatroomCardProps {
  chatroom: ProximityChatroom;
  onJoin: (id: number) => void;
}

export function ProximityChatroomCard({ chatroom, onJoin }: ProximityChatroomCardProps) {
  return (
    <div className="bg-white rounded-lg shadow-sm border p-6 hover:shadow-md transition-shadow">
      <div className="flex items-start justify-between">
        <div className="flex-1">
          <div className="flex items-center space-x-3 mb-2">
            <h3 className="text-lg font-semibold text-gray-900">{chatroom.name}</h3>
            <span className={`px-2 py-1 text-xs font-medium rounded-full ${
              chatroom.type === 'networking' ? 'bg-blue-100 text-blue-800' :
              chatroom.type === 'social' ? 'bg-green-100 text-green-800' :
              chatroom.type === 'dating' ? 'bg-pink-100 text-pink-800' :
              'bg-gray-100 text-gray-800'
            }`}>
              {chatroom.type}
            </span>
            {!chatroom.is_public && (
              <span className="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                Private
              </span>
            )}
          </div>
          
          <p className="text-gray-600 mb-3">{chatroom.description}</p>
          
          <div className="flex items-center space-x-6 text-sm text-gray-500">
            <div className="flex items-center space-x-1">
              <Users className="h-4 w-4" />
              <span>{chatroom.member_count} members</span>
            </div>
            <div className="flex items-center space-x-1">
              <MapPin className="h-4 w-4" />
              <span>{Math.round(chatroom.distance_meters || 0)}m away</span>
            </div>
            <div className="flex items-center space-x-1">
              <MessageCircle className="h-4 w-4" />
              <span>Active {chatroom.last_activity_at ? new Date(chatroom.last_activity_at).toLocaleDateString() : 'Recently'}</span>
            </div>
          </div>
        </div>
        
        <button
          onClick={() => onJoin(chatroom.id)}
          className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 ml-4"
        >
          Join Chatroom
        </button>
      </div>
    </div>
  );
}
```

#### Message Component
```tsx
// components/ProximityMessage.tsx
interface ProximityMessageProps {
  message: ProximityChatroomMessage;
  onReaction: (messageId: number, emoji: string) => void;
  onPin: (messageId: number) => void;
  onUnpin: (messageId: number) => void;
  isOwner: boolean;
}

export function ProximityMessage({ message, onReaction, onPin, onUnpin, isOwner }: ProximityMessageProps) {
  return (
    <div className="flex items-start space-x-3">
      <div className="flex-shrink-0">
        <div className="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
          {message.user?.name?.charAt(0) || 'U'}
        </div>
      </div>
      <div className="flex-1 min-w-0">
        <div className="flex items-center space-x-2 mb-1">
          <span className="text-sm font-medium text-gray-900">
            {message.user?.name || 'Anonymous'}
          </span>
          <span className="text-xs text-gray-500">
            {new Date(message.created_at).toLocaleTimeString()}
          </span>
          {message.is_pinned && (
            <Pin className="h-3 w-3 text-yellow-500" />
          )}
        </div>
        <p className="text-sm text-gray-700 mb-2">{message.content}</p>
        
        {/* Reactions */}
        {message.reactions && message.reactions.length > 0 && (
          <div className="flex items-center space-x-2 mb-2">
            {message.reactions.map((reaction, index) => (
              <button
                key={index}
                onClick={() => onReaction(message.id, reaction.emoji)}
                className="flex items-center space-x-1 text-xs bg-gray-100 rounded-full px-2 py-1 hover:bg-gray-200"
              >
                <span>{reaction.emoji}</span>
                <span>{reaction.count}</span>
              </button>
            ))}
          </div>
        )}

        {/* Message Actions */}
        <div className="flex items-center space-x-4 text-xs text-gray-500">
          <button
            onClick={() => onReaction(message.id, '👍')}
            className="hover:text-blue-600 flex items-center space-x-1"
          >
            <ThumbsUp className="h-3 w-3" />
            <span>Like</span>
          </button>
          <button
            onClick={() => onReaction(message.id, '❤️')}
            className="hover:text-red-600 flex items-center space-x-1"
          >
            <Heart className="h-3 w-3" />
            <span>Love</span>
          </button>
          {isOwner && (
            <button
              onClick={() => message.is_pinned ? onUnpin(message.id) : onPin(message.id)}
              className="hover:text-yellow-600 flex items-center space-x-1"
            >
              {message.is_pinned ? <PinOff className="h-3 w-3" /> : <Pin className="h-3 w-3" />}
              <span>{message.is_pinned ? 'Unpin' : 'Pin'}</span>
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
```

### Dashboard Integration

#### Dashboard Card
```tsx
// app/dashboard/page.tsx - Updated with proximity chatrooms card
<div className="bg-gradient-to-br from-blue-50 to-indigo-50 p-6 rounded-lg shadow border border-blue-200">
  <h3 className="text-lg font-medium text-gray-900 mb-2">🌐 Proximity Chatrooms</h3>
  <p className="text-gray-600 mb-4">
    Connect with people nearby for networking, social interaction, and professional opportunities
  </p>
  <Link
    href="/proximity-chatrooms"
    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700"
  >
    Find Nearby Chatrooms
  </Link>
</div>
```

This frontend implementation provides a complete, production-ready interface for the proximity chatroom system with real-time messaging, location-based discovery, and comprehensive user interaction features.