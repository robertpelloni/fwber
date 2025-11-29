/**
 * Comprehensive API Response Type Definitions
 *
 * This file contains TypeScript interfaces for all API responses from the FWBer backend.
 * These types ensure type safety across the frontend application and make API integration easier.
 */

// ============================================================================
// Base Types
// ============================================================================

export type MessageType = 'text' | 'image' | 'video' | 'audio' | 'file'

export type Gender = 'male' | 'female' | 'non-binary' | 'other'

export type RelationshipStyle = 'monogamous' | 'non-monogamous' | 'open' | 'polyamorous' | 'other'

export type PhotoType = 'ai' | 'real' | 'public'

export type RelationshipTierName =
  | 'matched'
  | 'chatting'
  | 'connected'
  | 'close_friends'
  | 'intimate'
  | 'committed'

// ============================================================================
// User & Profile Types
// ============================================================================

export interface User {
  id: number
  name: string
  email: string
  email_verified_at?: string | null
  created_at: string
  updated_at: string
  profile?: UserProfile
  photos?: Photo[]
  location?: UserLocation
}

export interface UserProfile {
  id: number
  user_id: number
  display_name: string | null
  date_of_birth: string | null
  gender: Gender | null
  pronouns: string | null
  sexual_orientation: string | null
  relationship_style: RelationshipStyle | null
  bio: string | null
  location_latitude: number | null
  location_longitude: number | null
  location_description: string | null
  sti_status: Record<string, any> | null
  preferences: Record<string, any> | null
  avatar_url: string | null
  looking_for: string[] | null
  created_at: string
  updated_at: string
}

export interface UserLocation {
  id: number
  user_id: number
  latitude: number
  longitude: number
  accuracy: number | null
  updated_at: string
  created_at: string
}

// ============================================================================
// Photo Types
// ============================================================================

export interface Photo {
  id: number
  user_id: number
  filename: string
  original_filename: string
  file_path: string
  thumbnail_path: string | null
  mime_type: string
  file_size: number
  width: number | null
  height: number | null
  is_primary: boolean
  is_private: boolean
  sort_order: number
  photo_type?: PhotoType
  metadata: Record<string, any> | null
  url?: string
  thumbnail_url?: string
  created_at: string
  updated_at: string
}

export interface PhotoUploadRequest {
  file: File
  is_primary?: boolean
  is_private?: boolean
  sort_order?: number
  photo_type?: PhotoType
}

export interface PhotoUploadResponse {
  photo: Photo
  message: string
}

// ============================================================================
// Match Types
// ============================================================================

export interface Match {
  id: number
  user1_id: number
  user2_id: number
  is_active: boolean
  last_message_at: string | null
  created_at: string
  updated_at: string
  user1?: User
  user2?: User
  relationship_tier?: RelationshipTier
  messages_count?: number
  unread_count?: number
}

export interface MatchListResponse {
  matches: Match[]
  total: number
  page: number
  per_page: number
}

export interface CreateMatchRequest {
  user_id: number
  action: 'like' | 'super_like' | 'pass'
}

export interface CreateMatchResponse {
  match: Match | null
  is_mutual: boolean
  message: string
}

// ============================================================================
// Message Types
// ============================================================================

export interface Message {
  id: number
  sender_id: number
  receiver_id: number
  content: string
  message_type: MessageType
  is_read: boolean
  sent_at: string
  read_at: string | null
  created_at: string
  updated_at: string
  sender?: User
  receiver?: User
}

export interface MessageListResponse {
  messages: Message[]
  total: number
  page: number
  per_page: number
  has_more: boolean
}

export interface SendMessageRequest {
  receiver_id: number
  content: string
  message_type?: MessageType
}

export interface SendMessageResponse {
  message: Message
  success: boolean
}

export interface MarkMessagesReadRequest {
  message_ids: number[]
}

export interface MarkMessagesReadResponse {
  updated_count: number
  success: boolean
}

// ============================================================================
// Relationship Tier Types
// ============================================================================

export interface RelationshipTier {
  id: number
  match_id: number
  current_tier: RelationshipTierName
  messages_exchanged: number
  days_connected: number
  has_met_in_person: boolean
  first_matched_at: string
  last_tier_upgrade_at: string | null
  created_at: string
  updated_at: string
}

export interface TierInfo {
  name: string
  icon: string
  color: string
  unlocks: string[]
  description?: string
}

export interface TierResponse {
  match_id: number
  current_tier: RelationshipTierName
  messages_exchanged: number
  days_connected: number
  has_met_in_person: boolean
  tier_info: TierInfo
  created_at: string
  updated_at: string
}

export interface TierUpdateRequest {
  increment_messages?: boolean
  mark_met_in_person?: boolean
}

export interface TierUpdateResponse extends TierResponse {
  previous_tier: RelationshipTierName
  tier_upgraded: boolean
}

export interface PhotoAccessInfo {
  id: number
  url: string
  thumbnail_url: string | null
  type: PhotoType
  is_primary: boolean
  blurred: boolean
}

export interface TierPhotosResponse {
  match_id: number
  current_tier: RelationshipTierName
  ai_photos: PhotoAccessInfo[]
  real_photos: {
    visible: PhotoAccessInfo[]
    blurred: PhotoAccessInfo[]
    locked: number
  }
  unlock_requirements: {
    next_tier: RelationshipTierName | null
    requirements: Array<{
      description: string
      met: boolean
    }>
  }
}

// ============================================================================
// Discovery & Recommendations
// ============================================================================

export interface DiscoveryProfile {
  id: number
  name: string
  age: number | null
  bio: string | null
  distance: number | null
  compatibility_score: number | null
  photos: string[]
  profile: Partial<UserProfile>
}

export interface DiscoveryResponse {
  profiles: DiscoveryProfile[]
  total: number
  filters_applied: {
    max_distance?: number
    min_age?: number
    max_age?: number
    gender?: Gender[]
  }
}

export interface SwipeAction {
  user_id: number
  action: 'like' | 'pass' | 'super_like'
}

export interface SwipeResponse {
  success: boolean
  match: Match | null
  is_mutual_like: boolean
}

// ============================================================================
// Event Types
// ============================================================================

export interface Event {
  id: number
  title: string
  description: string | null
  event_type: string
  start_time: string
  end_time: string
  venue_id: number | null
  capacity: number | null
  price: number | null
  is_public: boolean
  created_at: string
  updated_at: string
  venue?: Venue
  attendees_count?: number
  is_attending?: boolean
}

export interface Venue {
  id: number
  name: string
  address: string | null
  city: string | null
  state: string | null
  zip_code: string | null
  latitude: number | null
  longitude: number | null
  created_at: string
  updated_at: string
}

export interface EventListResponse {
  events: Event[]
  total: number
  page: number
  per_page: number
}

export interface EventAttendRequest {
  event_id: number
}

export interface EventAttendResponse {
  success: boolean
  is_attending: boolean
  attendee_count: number
}

// ============================================================================
// Bulletin Board Types
// ============================================================================

export interface BulletinBoard {
  id: number
  name: string
  description: string | null
  is_public: boolean
  requires_tier: RelationshipTierName | null
  created_at: string
  updated_at: string
  messages_count?: number
}

export interface BulletinMessage {
  id: number
  bulletin_board_id: number
  user_id: number
  content: string
  metadata: Record<string, any> | null
  is_anonymous: boolean
  is_moderated: boolean
  expires_at: string | null
  reaction_count: number
  reply_count: number
  created_at: string
  updated_at: string
  user?: User
  reactions?: BulletinReaction[]
}

export interface BulletinReaction {
  user_id: number
  reaction_type: string
  created_at: string
}

export interface BulletinMessageListResponse {
  messages: BulletinMessage[]
  total: number
  page: number
  per_page: number
}

export interface PostBulletinMessageRequest {
  bulletin_board_id: number
  content: string
  is_anonymous?: boolean
}

export interface PostBulletinMessageResponse {
  message: BulletinMessage
  success: boolean
}

// ============================================================================
// Analytics Types
// ============================================================================

export interface AnalyticsData {
  profile_views: number
  likes_received: number
  likes_sent: number
  matches: number
  messages_sent: number
  messages_received: number
  super_likes_received: number
  super_likes_sent: number
  active_conversations: number
  response_rate: number
  avg_response_time_minutes: number
  period_start: string
  period_end: string
}

export interface AnalyticsTimeSeriesData {
  date: string
  profile_views: number
  likes: number
  matches: number
  messages: number
}

export interface AnalyticsResponse {
  summary: AnalyticsData
  time_series: AnalyticsTimeSeriesData[]
  top_matches: Array<{
    match_id: number
    user: User
    messages_count: number
    last_message_at: string
  }>
}

// ============================================================================
// Admin Analytics & Ops Types
// ============================================================================

export type AnalyticsRange = '1d' | '7d' | '30d' | '90d'

export interface PlatformAnalyticsUserStats {
  total: number
  active: number
  new_today: number
  growth_rate: number
}

export interface PlatformAnalyticsMessageStats {
  total: number
  today: number
  average_per_user: number
  moderation_stats: {
    flagged: number
    approved: number
    rejected: number
    pending_review: number
  }
}

export interface PlatformAnalyticsLocationStats {
  total_boards: number
  active_areas: number
  coverage_radius: number
  most_active: Array<{
    name: string
    message_count: number
    active_users: number
  }>
}

export interface PlatformAnalyticsPerformanceStats {
  api_response_time: number
  sse_connections: number
  cache_hit_rate: number
  error_rate: number
}

export interface PlatformAnalyticsTrends {
  hourly_activity: Array<{ hour: number; messages: number; users: number }>
  daily_activity: Array<{ date: string; messages: number; users: number }>
  top_categories: Array<{ category: string; count: number }>
}

export interface PlatformAnalyticsResponse {
  users: PlatformAnalyticsUserStats
  messages: PlatformAnalyticsMessageStats
  locations: PlatformAnalyticsLocationStats
  performance: PlatformAnalyticsPerformanceStats
  trends: PlatformAnalyticsTrends
}

export interface RealtimeMetrics {
  active_connections: number
  messages_per_minute: number
  new_users_last_hour: number
  system_load: number
  memory_usage: number
  disk_usage: number
}

export interface ModerationInsights {
  ai_accuracy: number
  human_review_rate: number
  false_positive_rate: number
  average_review_time: number
  top_flagged_categories: Array<{ category: string; count: number }>
}

export type RateLimitTimeframe = '1h' | '24h'

export interface RateLimitConfig {
  capacity: number
  refill_rate: number
  cost_per_request: number
  burst_allowance: number
}

export interface RateLimitActionStats {
  active_buckets: number
  config: RateLimitConfig
}

export interface RateLimitStatistics {
  total_keys: number
  timeframe: string
  actions: Record<string, RateLimitActionStats>
}

export interface RateLimitStatsResponse {
  timeframe: string
  statistics: RateLimitStatistics
  timestamp: string
}

// ============================================================================
// Chatroom Types (Proximity & Group)
// ============================================================================

export interface Chatroom {
  id: number
  name: string
  description: string | null
  is_public: boolean
  max_members: number | null
  created_by: number
  created_at: string
  updated_at: string
  members_count?: number
  is_member?: boolean
}

export interface ProximityChatroom {
  id: number
  name: string
  latitude: number
  longitude: number
  radius_meters: number
  is_active: boolean
  expires_at: string | null
  created_by: number
  created_at: string
  updated_at: string
  members_count?: number
  distance?: number
}

export interface ChatroomMessage {
  id: number
  chatroom_id: number
  user_id: number
  content: string
  message_type: MessageType
  created_at: string
  updated_at: string
  user?: User
  reactions?: ChatroomReaction[]
}

export interface ChatroomReaction {
  id: number
  user_id: number
  reaction: string
  created_at: string
  user?: User
}

export interface ChatroomListResponse {
  chatrooms: Chatroom[]
  total: number
  page: number
  per_page: number
}

// ============================================================================
// Subscription Types
// ============================================================================

export interface Subscription {
  id: number
  user_id: number
  plan_name: string
  stripe_subscription_id: string | null
  status: 'active' | 'canceled' | 'past_due' | 'trialing'
  current_period_start: string
  current_period_end: string
  cancel_at_period_end: boolean
  created_at: string
  updated_at: string
}

export interface SubscriptionPlan {
  id: string
  name: string
  description: string
  price: number
  interval: 'month' | 'year'
  features: string[]
  is_popular?: boolean
}

export interface CreateSubscriptionRequest {
  plan_id: string
  payment_method_id: string
}

export interface CreateSubscriptionResponse {
  subscription: Subscription
  client_secret?: string
  requires_action: boolean
  success: boolean
}

// ============================================================================
// WebSocket Event Types
// ============================================================================

export interface WebSocketMessage {
  type: string
  data: any
  timestamp: string
}

export interface NewMessageEvent {
  type: 'message.new'
  data: Message
}

export interface MessageReadEvent {
  type: 'message.read'
  data: {
    message_id: number
    read_by: number
    read_at: string
  }
}

export interface MatchEvent {
  type: 'match.new'
  data: Match
}

export interface TypingEvent {
  type: 'user.typing'
  data: {
    user_id: number
    conversation_id: number
    is_typing: boolean
  }
}

export interface OnlineStatusEvent {
  type: 'user.online'
  data: {
    user_id: number
    is_online: boolean
    last_seen: string
  }
}

export type WebSocketEvent =
  | NewMessageEvent
  | MessageReadEvent
  | MatchEvent
  | TypingEvent
  | OnlineStatusEvent
  | WebSocketMessage

// ============================================================================
// Error Response Types
// ============================================================================

export interface ValidationError {
  field: string
  message: string
}

export interface ApiErrorResponse {
  message: string
  errors?: ValidationError[]
  code?: string
  status: number
  timestamp: string
}

// ============================================================================
// Pagination Types
// ============================================================================

export interface PaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  per_page: number
  to: number | null
  total: number
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginationMeta
  links?: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
}

// ============================================================================
// Generic Response Wrappers
// ============================================================================

export interface SuccessResponse<T = any> {
  success: true
  data: T
  message?: string
}

export interface ErrorResponse {
  success: false
  error: ApiErrorResponse
}

export type ApiResponse<T = any> = SuccessResponse<T> | ErrorResponse

// ============================================================================
// Type Guards
// ============================================================================

export function isSuccessResponse<T>(response: ApiResponse<T>): response is SuccessResponse<T> {
  return response.success === true
}

export function isErrorResponse(response: ApiResponse): response is ErrorResponse {
  return response.success === false
}

export function isValidationError(error: ApiErrorResponse): error is ApiErrorResponse & { errors: ValidationError[] } {
  return Array.isArray(error.errors) && error.errors.length > 0
}

// ============================================================================
// Utility Types
// ============================================================================

/**
 * Makes specified properties optional
 */
export type PartialBy<T, K extends keyof T> = Omit<T, K> & Partial<Pick<T, K>>

/**
 * Makes specified properties required
 */
export type RequiredBy<T, K extends keyof T> = Omit<T, K> & Required<Pick<T, K>>

/**
 * Extracts the data type from a paginated response
 */
export type UnwrapPaginated<T> = T extends PaginatedResponse<infer U> ? U : never

/**
 * Extracts the data type from an API response
 */
export type UnwrapApiResponse<T> = T extends ApiResponse<infer U> ? U : never
