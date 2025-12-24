// Proximity Artifacts & Local Pulse Types

export type ArtifactType = 'chat' | 'board_post' | 'announce' | 'token_drop';

export interface ProximityArtifact {
  id: number;
  type: ArtifactType;
  content: string;
  lat: number;
  lng: number;
  radius: number;
  expires_at: string;
  created_at: string;
  user_id?: number;
  meta?: {
    amount?: number;
    claimed?: boolean;
    claimed_by?: number;
  };
}

export interface MatchCandidate {
  user_id: number;
  age: number;
  gender: string;
  distance_miles: number;
  compatibility_indicators: string[];
  last_seen: string | null;
}

export interface LocalPulseResponse {
  artifacts: ProximityArtifact[];
  candidates: MatchCandidate[];
  meta: {
    center_lat: number;
    center_lng: number;
    radius_m: number;
    artifacts_count: number;
    candidates_count: number;
  };
}

export interface CreateArtifactRequest {
  type: ArtifactType;
  content: string;
  lat: number;
  lng: number;
  radius?: number;
  amount?: number;
}

export interface LocalPulseParams {
  lat: number;
  lng: number;
  radius?: number;
}

export interface ProximityChatroom {
  id: number;
  name: string;
  description?: string;
  lat: number;
  lng: number;
  radius: number;
  active_members_count: number;
  created_at: string;
  expires_at?: string;
  is_member?: boolean;
}
