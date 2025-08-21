# FWBer.me Revival - Next.js + Laravel + GenAI Implementation

## Technology Stack

### Frontend: Next.js 14
- **App Router** for modern routing
- **Server Components** for better performance
- **TypeScript** for type safety
- **Tailwind CSS** for styling
- **React Hook Form** for form handling
- **Zustand** for state management
- **Socket.io-client** for real-time features

### Backend: Laravel 11
- **Laravel Sanctum** for API authentication
- **Laravel Queue** for background jobs
- **Laravel Broadcasting** for real-time features
- **Laravel Scout** for search functionality
- **Laravel Horizon** for queue monitoring
- **Laravel Telescope** for debugging

### AI: GenAI Integration
- **Google Gemini** for avatar generation
- **Stable Diffusion** as backup
- **Custom fine-tuned models** for consistent style

## Project Structure

```
fwber-modern/
├── frontend/                 # Next.js application
│   ├── app/                 # App router pages
│   ├── components/          # Reusable components
│   ├── lib/                 # Utilities and services
│   ├── hooks/               # Custom React hooks
│   ├── types/               # TypeScript definitions
│   └── public/              # Static assets
├── backend/                 # Laravel application
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Middleware/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Jobs/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/
└── shared/                  # Shared types and utilities
```

## Phase 1: Backend Setup (Laravel)

### 1.1 Laravel Project Setup

```bash
# Create Laravel project
composer create-project laravel/laravel backend
cd backend

# Install required packages
composer require laravel/sanctum
composer require laravel/broadcasting
composer require laravel/scout
composer require predis/predis
composer require google/cloud-ai-platform
composer require intervention/image
composer require spatie/laravel-permission
composer require spatie/laravel-backup
```

### 1.2 Database Migrations

```php
// database/migrations/2024_01_01_000001_create_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->string('gender');
            $table->string('body_type');
            $table->string('ethnicity');
            $table->string('hair_color');
            $table->string('hair_length');
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_lon', 11, 8)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->boolean('profile_completed')->default(false);
            $table->boolean('verified')->default(false);
            $table->boolean('phone_verified')->default(false);
            $table->boolean('photo_verified')->default(false);
            $table->string('avatar_url')->nullable();
            $table->timestamps();
            
            $table->index(['location_lat', 'location_lon']);
            $table->index('gender');
            $table->index('verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

```php
// database/migrations/2024_01_01_000002_create_user_preferences_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('sti_status')->nullable();
            $table->string('relationship_style')->nullable();
            $table->json('venue_preferences')->nullable();
            $table->json('safety_preferences')->nullable();
            $table->json('communication_preferences')->nullable();
            $table->json('sexual_preferences')->nullable();
            $table->integer('age_range_min')->default(18);
            $table->integer('age_range_max')->default(99);
            $table->integer('distance_preference')->default(50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
```

```php
// database/migrations/2024_01_01_000003_create_presence_announcements_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presence_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('venue_name');
            $table->decimal('venue_lat', 10, 8);
            $table->decimal('venue_lon', 11, 8);
            $table->json('preferences');
            $table->string('privacy_level')->default('venue-only');
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['venue_lat', 'venue_lon']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presence_announcements');
    }
};
```

### 1.3 Laravel Models

```php
// app/Models/User.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'body_type',
        'ethnicity',
        'hair_color',
        'hair_length',
        'location_lat',
        'location_lon',
        'city',
        'state',
        'country',
        'postal_code',
        'profile_completed',
        'verified',
        'phone_verified',
        'photo_verified',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'location_lat' => 'decimal:8',
        'location_lon' => 'decimal:8',
        'profile_completed' => 'boolean',
        'verified' => 'boolean',
        'phone_verified' => 'boolean',
        'photo_verified' => 'boolean',
    ];

    public function preferences()
    {
        return $this->hasOne(UserPreference::class);
    }

    public function presenceAnnouncements()
    {
        return $this->hasMany(PresenceAnnouncement::class);
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth->age;
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
```

```php
// app/Models/UserPreference.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sti_status',
        'relationship_style',
        'venue_preferences',
        'safety_preferences',
        'communication_preferences',
        'sexual_preferences',
        'age_range_min',
        'age_range_max',
        'distance_preference',
    ];

    protected $casts = [
        'sti_status' => 'array',
        'venue_preferences' => 'array',
        'safety_preferences' => 'array',
        'communication_preferences' => 'array',
        'sexual_preferences' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### 1.4 GenAI Avatar Service

```php
// app/Services/AvatarGenerationService.php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class AvatarGenerationService
{
    private $genaiApiKey;
    private $genaiEndpoint;

    public function __construct()
    {
        $this->genaiApiKey = config('services.genai.api_key');
        $this->genaiEndpoint = config('services.genai.endpoint');
    }

    public function generateAvatar(User $user): string
    {
        try {
            $prompt = $this->buildAvatarPrompt($user);
            $avatarData = $this->callGenAI($prompt);
            
            $fileName = "avatars/avatar_{$user->id}_" . time() . ".png";
            $filePath = storage_path("app/public/{$fileName}");
            
            // Ensure directory exists
            Storage::disk('public')->makeDirectory('avatars');
            
            // Save and optimize image
            $this->saveAndOptimizeImage($avatarData, $filePath);
            
            return $fileName;
        } catch (\Exception $e) {
            \Log::error('Avatar generation failed: ' . $e->getMessage());
            return 'avatars/default.png';
        }
    }

    private function buildAvatarPrompt(User $user): string
    {
        $style = 'cartoon, professional, clean, adult-friendly, dating app style';
        $gender = $user->gender ?? 'person';
        $bodyType = $user->body_type ?? 'average';
        $ethnicity = $user->ethnicity ?? 'diverse';
        $hairColor = $user->hair_color ?? 'natural';
        $hairLength = $user->hair_length ?? 'medium';
        
        return "Create a {$style} avatar of a {$gender} with {$bodyType} body type, {$ethnicity} ethnicity, {$hairColor} {$hairLength} hair, professional appearance, suitable for dating app, no explicit content, high quality, 512x512 pixels";
    }

    private function callGenAI(string $prompt): string
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->genaiApiKey}",
            'Content-Type' => 'application/json',
        ])->post($this->genaiEndpoint, [
            'prompt' => $prompt,
            'negative_prompt' => 'explicit, nude, inappropriate, adult content, NSFW',
            'width' => 512,
            'height' => 512,
            'steps' => 20,
            'guidance_scale' => 7.5,
            'seed' => rand(1, 999999),
        ]);

        if (!$response->successful()) {
            throw new \Exception('GenAI API call failed: ' . $response->body());
        }

        $data = $response->json();
        return $data['images'][0] ?? throw new \Exception('No image data received');
    }

    private function saveAndOptimizeImage(string $base64Data, string $filePath): void
    {
        // Decode base64 image
        $imageData = base64_decode($base64Data);
        
        // Create image and optimize
        $image = Image::make($imageData);
        $image->resize(512, 512, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        // Save optimized image
        $image->save($filePath, 85, 'png');
    }

    public function regenerateAvatar(User $user): string
    {
        // Delete old avatar if exists
        if ($user->avatar_url && $user->avatar_url !== 'avatars/default.png') {
            Storage::disk('public')->delete($user->avatar_url);
        }
        
        return $this->generateAvatar($user);
    }
}
```

### 1.5 Authentication Controller

```php
// app/Http/Controllers/Api/AuthController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AvatarGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    private $avatarService;

    public function __construct(AvatarGenerationService $avatarService)
    {
        $this->avatarService = $avatarService;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'password' => ['required', Password::defaults()],
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'required|date|before:-18 years',
            'gender' => 'required|string|in:male,female,mtf,ftm,cdmtf,cdftm,mf,mm,ff,group',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
        ]);

        // Generate initial avatar
        $avatarUrl = $this->avatarService->generateAvatar($user);
        $user->update(['avatar_url' => $avatarUrl]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user->load('preferences'),
            'token' => $token,
            'message' => 'Registration successful. Please verify your email.'
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();
        
        if (!$user->verified) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email before logging in'
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user->load('preferences'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()->load('preferences')
        ]);
    }
}
```

### 1.6 User Profile Controller

```php
// app/Http/Controllers/Api/UserController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\AvatarGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    private $avatarService;

    public function __construct(AvatarGenerationService $avatarService)
    {
        $this->avatarService = $avatarService;
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'body_type' => 'sometimes|string|in:tiny,slim,average,muscular,curvy,thick,bbw',
            'ethnicity' => 'sometimes|string|in:white,asian,latino,indian,black,other',
            'hair_color' => 'sometimes|string|in:light,medium,dark,red,gray,other',
            'hair_length' => 'sometimes|string|in:bald,short,medium,long',
            'location_lat' => 'sometimes|numeric|between:-90,90',
            'location_lon' => 'sometimes|numeric|between:-180,180',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'country' => 'sometimes|string|max:100',
            'postal_code' => 'sometimes|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->fails()
            ], 422);
        }

        $user->update($request->only([
            'first_name', 'last_name', 'body_type', 'ethnicity',
            'hair_color', 'hair_length', 'location_lat', 'location_lon',
            'city', 'state', 'country', 'postal_code'
        ]));

        // Regenerate avatar if physical attributes changed
        if ($request->hasAny(['body_type', 'ethnicity', 'hair_color', 'hair_length'])) {
            $avatarUrl = $this->avatarService->regenerateAvatar($user);
            $user->update(['avatar_url' => $avatarUrl]);
        }

        $user->update(['profile_completed' => true]);

        return response()->json([
            'success' => true,
            'user' => $user->load('preferences'),
            'message' => 'Profile updated successfully'
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'sti_status' => 'sometimes|array',
            'relationship_style' => 'sometimes|string',
            'venue_preferences' => 'sometimes|array',
            'safety_preferences' => 'sometimes|array',
            'communication_preferences' => 'sometimes|array',
            'sexual_preferences' => 'sometimes|array',
            'age_range_min' => 'sometimes|integer|min:18|max:99',
            'age_range_max' => 'sometimes|integer|min:18|max:99',
            'distance_preference' => 'sometimes|integer|min:1|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            $request->all()
        );

        return response()->json([
            'success' => true,
            'user' => $user->load('preferences'),
            'message' => 'Preferences updated successfully'
        ]);
    }

    public function regenerateAvatar(Request $request)
    {
        $user = $request->user();
        $avatarUrl = $this->avatarService->regenerateAvatar($user);

        return response()->json([
            'success' => true,
            'avatar_url' => $avatarUrl,
            'message' => 'Avatar regenerated successfully'
        ]);
    }
}
```

## Phase 2: Frontend Setup (Next.js)

### 2.1 Next.js Project Setup

```bash
# Create Next.js project
npx create-next-app@latest frontend --typescript --tailwind --app --src-dir
cd frontend

# Install dependencies
npm install @tanstack/react-query
npm install axios
npm install react-hook-form
npm install @hookform/resolvers zod
npm install zustand
npm install socket.io-client
npm install lucide-react
npm install @headlessui/react
npm install clsx tailwind-merge
npm install react-hot-toast
```

### 2.2 API Client Setup

```typescript
// src/lib/api.ts
import axios from 'axios';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor to add auth token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export const authApi = {
  register: (data: RegisterData) => api.post('/auth/register', data),
  login: (data: LoginData) => api.post('/auth/login', data),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
};

export const userApi = {
  updateProfile: (data: ProfileData) => api.put('/users/profile', data),
  updatePreferences: (data: PreferencesData) => api.put('/users/preferences', data),
  regenerateAvatar: () => api.post('/users/avatar/regenerate'),
};

export const matchesApi = {
  getMatches: (filters?: MatchFilters) => api.get('/matches', { params: filters }),
  getMatch: (id: number) => api.get(`/matches/${id}`),
};

export const presenceApi = {
  announce: (data: PresenceData) => api.post('/presence/announce', data),
  getNearby: (radius?: number) => api.get('/presence/nearby', { params: { radius } }),
  remove: () => api.delete('/presence/remove'),
};
```

### 2.3 State Management (Zustand)

```typescript
// src/store/auth.ts
import { create } from 'zustand';
import { persist } from 'zustand/middleware';

interface User {
  id: number;
  email: string;
  first_name: string;
  last_name: string;
  avatar_url: string;
  profile_completed: boolean;
  preferences?: UserPreferences;
}

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  setUser: (user: User) => void;
  setToken: (token: string) => void;
  logout: () => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      setUser: (user) => set({ user, isAuthenticated: true }),
      setToken: (token) => set({ token }),
      logout: () => set({ user: null, token: null, isAuthenticated: false }),
    }),
    {
      name: 'auth-storage',
    }
  )
);
```

### 2.4 Authentication Components

```typescript
// src/components/auth/RegisterForm.tsx
'use client';

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { authApi } from '@/lib/api';
import { useAuthStore } from '@/store/auth';
import { toast } from 'react-hot-toast';

const registerSchema = z.object({
  email: z.string().email('Invalid email address'),
  password: z.string().min(8, 'Password must be at least 8 characters'),
  first_name: z.string().min(1, 'First name is required'),
  last_name: z.string().min(1, 'Last name is required'),
  date_of_birth: z.string().refine((date) => {
    const age = new Date().getFullYear() - new Date(date).getFullYear();
    return age >= 18;
  }, 'Must be 18 or older'),
  gender: z.enum(['male', 'female', 'mtf', 'ftm', 'cdmtf', 'cdftm', 'mf', 'mm', 'ff', 'group']),
});

type RegisterData = z.infer<typeof registerSchema>;

export default function RegisterForm() {
  const [isLoading, setIsLoading] = useState(false);
  const { setUser, setToken } = useAuthStore();
  
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<RegisterData>({
    resolver: zodResolver(registerSchema),
  });

  const onSubmit = async (data: RegisterData) => {
    setIsLoading(true);
    try {
      const response = await authApi.register(data);
      const { user, token } = response.data;
      
      setUser(user);
      setToken(token);
      localStorage.setItem('auth_token', token);
      
      toast.success('Registration successful!');
      // Redirect to profile completion
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Registration failed');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <div>
        <label className="block text-sm font-medium text-gray-700">
          Email
        </label>
        <input
          type="email"
          {...register('email')}
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
        {errors.email && (
          <p className="mt-1 text-sm text-red-600">{errors.email.message}</p>
        )}
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700">
          Password
        </label>
        <input
          type="password"
          {...register('password')}
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
        {errors.password && (
          <p className="mt-1 text-sm text-red-600">{errors.password.message}</p>
        )}
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium text-gray-700">
            First Name
          </label>
          <input
            type="text"
            {...register('first_name')}
            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
          {errors.first_name && (
            <p className="mt-1 text-sm text-red-600">{errors.first_name.message}</p>
          )}
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Last Name
          </label>
          <input
            type="text"
            {...register('last_name')}
            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
          {errors.last_name && (
            <p className="mt-1 text-sm text-red-600">{errors.last_name.message}</p>
          )}
        </div>
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700">
          Date of Birth
        </label>
        <input
          type="date"
          {...register('date_of_birth')}
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
        {errors.date_of_birth && (
          <p className="mt-1 text-sm text-red-600">{errors.date_of_birth.message}</p>
        )}
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700">
          Gender
        </label>
        <select
          {...register('gender')}
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
          <option value="">Select gender</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="mtf">Male to Female</option>
          <option value="ftm">Female to Male</option>
          <option value="cdmtf">Crossdresser (MTF)</option>
          <option value="cdftm">Crossdresser (FTM)</option>
          <option value="mf">Couple (MF)</option>
          <option value="mm">Couple (MM)</option>
          <option value="ff">Couple (FF)</option>
          <option value="group">Group</option>
        </select>
        {errors.gender && (
          <p className="mt-1 text-sm text-red-600">{errors.gender.message}</p>
        )}
      </div>

      <button
        type="submit"
        disabled={isLoading}
        className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
      >
        {isLoading ? 'Creating account...' : 'Create Account'}
      </button>
    </form>
  );
}
```

### 2.5 Avatar Display Component

```typescript
// src/components/profile/AvatarDisplay.tsx
'use client';

import { useState } from 'react';
import { userApi } from '@/lib/api';
import { toast } from 'react-hot-toast';
import { RefreshCw } from 'lucide-react';

interface AvatarDisplayProps {
  avatarUrl: string;
  userId: number;
  onAvatarUpdate?: (newUrl: string) => void;
}

export default function AvatarDisplay({ avatarUrl, userId, onAvatarUpdate }: AvatarDisplayProps) {
  const [isRegenerating, setIsRegenerating] = useState(false);

  const handleRegenerate = async () => {
    setIsRegenerating(true);
    try {
      const response = await userApi.regenerateAvatar();
      const newAvatarUrl = response.data.avatar_url;
      onAvatarUpdate?.(newAvatarUrl);
      toast.success('Avatar regenerated successfully!');
    } catch (error) {
      toast.error('Failed to regenerate avatar');
    } finally {
      setIsRegenerating(false);
    }
  };

  return (
    <div className="relative group">
      <img
        src={`${process.env.NEXT_PUBLIC_API_URL}/storage/${avatarUrl}`}
        alt="User avatar"
        className="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg"
      />
      
      <button
        onClick={handleRegenerate}
        disabled={isRegenerating}
        className="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200"
      >
        <RefreshCw className={`w-6 h-6 text-white ${isRegenerating ? 'animate-spin' : ''}`} />
      </button>
      
      {isRegenerating && (
        <div className="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-full">
          <div className="text-white text-sm">Generating...</div>
        </div>
      )}
    </div>
  );
}
```

## Phase 3: Environment Configuration

### 3.1 Laravel Environment Variables

```bash
# .env (Laravel)
APP_NAME="FWBer"
APP_ENV=local
APP_KEY=base64:your-app-key
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fwber
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# GenAI Configuration
GENAI_API_KEY=your-genai-api-key
GENAI_ENDPOINT=https://api.genai.com/v1/generation

# File Storage
FILESYSTEM_DISK=public

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000

# CORS
CORS_ALLOWED_ORIGINS=http://localhost:3000
```

### 3.2 Next.js Environment Variables

```bash
# .env.local (Next.js)
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_APP_NAME=FWBer
NEXT_PUBLIC_APP_URL=http://localhost:3000
```

## Phase 4: Deployment Setup

### 4.1 Docker Configuration

```dockerfile
# backend/Dockerfile
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]
```

```dockerfile
# frontend/Dockerfile
FROM node:18-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci --only=production

COPY . .
RUN npm run build

EXPOSE 3000
CMD ["npm", "start"]
```

### 4.2 Docker Compose

```yaml
# docker-compose.yml
version: '3.8'

services:
  backend:
    build: ./backend
    ports:
      - "8000:9000"
    volumes:
      - ./backend:/var/www
    depends_on:
      - mysql
      - redis
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis

  frontend:
    build: ./frontend
    ports:
      - "3000:3000"
    volumes:
      - ./frontend:/app
    depends_on:
      - backend

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: fwber
      MYSQL_ROOT_PASSWORD: password
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

volumes:
  mysql_data:
```

## Next Steps

1. **Set up development environment** with Docker
2. **Configure GenAI API** for avatar generation
3. **Implement the authentication flow** with Laravel Sanctum
4. **Build the profile completion flow** with avatar generation
5. **Add real-time features** with Laravel Broadcasting
6. **Implement the matching algorithm** with enhanced preferences
7. **Add location-based presence** features
8. **Deploy to production** with proper security measures

This implementation provides a solid foundation for your FWBer.me revival using modern technologies while maintaining the core features and adding the enhanced capabilities you requested.
