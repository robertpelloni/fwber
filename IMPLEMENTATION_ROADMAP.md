# FWBer.com Implementation Roadmap

## Phase 1: Foundation Setup (Weeks 1-8)

### Week 1-2: Project Setup & Architecture

#### 1.1 Initialize Node.js Backend
```bash
# Create project structure
mkdir fwber-modern
cd fwber-modern
npm init -y

# Install core dependencies
npm install express cors helmet morgan dotenv bcryptjs jsonwebtoken
npm install -D nodemon @types/node typescript

# Install database dependencies
npm install mysql2 sequelize redis
```

#### 1.2 Basic Express Server Setup
```javascript
// src/server.js
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const morgan = require('morgan');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(helmet());
app.use(cors());
app.use(morgan('combined'));
app.use(express.json());

// Routes
app.use('/api/v1/auth', require('./routes/auth'));
app.use('/api/v1/users', require('./routes/users'));
app.use('/api/v1/matches', require('./routes/matches'));

app.listen(PORT, () => {
  console.log(`FWBer server running on port ${PORT}`);
});
```

#### 1.3 Database Migration Script
```javascript
// src/database/migrate.js
const { Sequelize } = require('sequelize');
const fs = require('fs');
const path = require('path');

const sequelize = new Sequelize(process.env.DATABASE_URL, {
  dialect: 'mysql',
  logging: false
});

// Migration to create modern user table
const createUsersTable = `
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  date_of_birth DATE,
  gender VARCHAR(50),
  body_type VARCHAR(50),
  ethnicity VARCHAR(50),
  hair_color VARCHAR(50),
  hair_length VARCHAR(50),
  location_lat DECIMAL(10, 8),
  location_lon DECIMAL(11, 8),
  city VARCHAR(100),
  state VARCHAR(100),
  country VARCHAR(100),
  postal_code VARCHAR(20),
  profile_completed BOOLEAN DEFAULT FALSE,
  verified BOOLEAN DEFAULT FALSE,
  phone_verified BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_location (location_lat, location_lon),
  INDEX idx_gender (gender),
  INDEX idx_verified (verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
`;

// Migration to create preferences table
const createPreferencesTable = `
CREATE TABLE IF NOT EXISTS user_preferences (
  user_id INT PRIMARY KEY,
  sti_status JSON,
  relationship_style VARCHAR(100),
  venue_preferences JSON,
  safety_preferences JSON,
  communication_preferences JSON,
  sexual_preferences JSON,
  age_range_min INT DEFAULT 18,
  age_range_max INT DEFAULT 99,
  distance_preference INT DEFAULT 50,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
`;

async function runMigrations() {
  try {
    await sequelize.authenticate();
    console.log('Database connected successfully.');
    
    await sequelize.query(createUsersTable);
    console.log('Users table created.');
    
    await sequelize.query(createPreferencesTable);
    console.log('Preferences table created.');
    
    process.exit(0);
  } catch (error) {
    console.error('Migration failed:', error);
    process.exit(1);
  }
}

runMigrations();
```

### Week 3-4: Authentication & User Management

#### 2.1 Authentication Service
```javascript
// src/services/authService.js
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { User } = require('../models');

class AuthService {
  async register(userData) {
    const { email, password, firstName, lastName, dateOfBirth } = userData;
    
    // Validate age (18+)
    const age = this.calculateAge(dateOfBirth);
    if (age < 18) {
      throw new Error('Must be 18 or older to register');
    }
    
    // Check if user exists
    const existingUser = await User.findOne({ where: { email } });
    if (existingUser) {
      throw new Error('Email already registered');
    }
    
    // Hash password
    const saltRounds = 12;
    const passwordHash = await bcrypt.hash(password, saltRounds);
    
    // Create user
    const user = await User.create({
      email,
      password_hash: passwordHash,
      first_name: firstName,
      last_name: lastName,
      date_of_birth: dateOfBirth,
      verified: false
    });
    
    // Generate verification token
    const verificationToken = jwt.sign(
      { userId: user.id, email },
      process.env.JWT_SECRET,
      { expiresIn: '24h' }
    );
    
    return { user, verificationToken };
  }
  
  async login(email, password) {
    const user = await User.findOne({ where: { email } });
    if (!user) {
      throw new Error('Invalid credentials');
    }
    
    const isValidPassword = await bcrypt.compare(password, user.password_hash);
    if (!isValidPassword) {
      throw new Error('Invalid credentials');
    }
    
    if (!user.verified) {
      throw new Error('Please verify your email before logging in');
    }
    
    // Generate JWT token
    const token = jwt.sign(
      { userId: user.id, email: user.email },
      process.env.JWT_SECRET,
      { expiresIn: '7d' }
    );
    
    return { user, token };
  }
  
  calculateAge(dateOfBirth) {
    const today = new Date();
    const birthDate = new Date(dateOfBirth);
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }
    
    return age;
  }
}

module.exports = new AuthService();
```

#### 2.2 User Profile Management
```javascript
// src/services/userService.js
const { User, UserPreference } = require('../models');
const AvatarService = require('./avatarService');

class UserService {
  async updateProfile(userId, profileData) {
    const user = await User.findByPk(userId);
    if (!user) {
      throw new Error('User not found');
    }
    
    // Update basic profile
    await user.update({
      first_name: profileData.firstName,
      last_name: profileData.lastName,
      gender: profileData.gender,
      body_type: profileData.bodyType,
      ethnicity: profileData.ethnicity,
      hair_color: profileData.hairColor,
      hair_length: profileData.hairLength,
      location_lat: profileData.latitude,
      location_lon: profileData.longitude,
      city: profileData.city,
      state: profileData.state,
      country: profileData.country,
      postal_code: profileData.postalCode,
      profile_completed: true
    });
    
    // Update preferences
    await UserPreference.upsert({
      user_id: userId,
      sti_status: profileData.stiStatus,
      relationship_style: profileData.relationshipStyle,
      venue_preferences: profileData.venuePreferences,
      safety_preferences: profileData.safetyPreferences,
      communication_preferences: profileData.communicationPreferences,
      sexual_preferences: profileData.sexualPreferences,
      age_range_min: profileData.ageRangeMin,
      age_range_max: profileData.ageRangeMax,
      distance_preference: profileData.distancePreference
    });
    
    // Generate new avatar
    const avatarUrl = await AvatarService.generateAvatar(user);
    
    return { user, avatarUrl };
  }
  
  async getProfile(userId) {
    const user = await User.findByPk(userId, {
      include: [{ model: UserPreference, as: 'preferences' }]
    });
    
    if (!user) {
      throw new Error('User not found');
    }
    
    return user;
  }
}

module.exports = new UserService();
```

### Week 5-6: AI Avatar Generation

#### 3.1 Avatar Generation Service
```javascript
// src/services/avatarService.js
const axios = require('axios');
const fs = require('fs').promises;
const path = require('path');

class AvatarService {
  constructor() {
    this.stableDiffusionUrl = process.env.STABLE_DIFFUSION_URL;
    this.apiKey = process.env.STABLE_DIFFUSION_API_KEY;
  }
  
  async generateAvatar(user) {
    try {
      const prompt = this.buildAvatarPrompt(user);
      const avatarUrl = await this.callStableDiffusion(prompt);
      
      // Save avatar to storage
      const fileName = `avatar_${user.id}_${Date.now()}.png`;
      const filePath = path.join(__dirname, '../public/avatars', fileName);
      
      await this.downloadAndSave(avatarUrl, filePath);
      
      return `/avatars/${fileName}`;
    } catch (error) {
      console.error('Avatar generation failed:', error);
      // Fallback to default avatar
      return '/avatars/default.png';
    }
  }
  
  buildAvatarPrompt(user) {
    const style = 'cartoon, professional, clean, adult-friendly';
    const gender = user.gender || 'person';
    const bodyType = user.body_type || 'average';
    const ethnicity = user.ethnicity || 'diverse';
    const hairColor = user.hair_color || 'natural';
    const hairLength = user.hair_length || 'medium';
    
    return `Create a ${style} avatar of a ${gender} with ${bodyType} body type, ${ethnicity} ethnicity, ${hairColor} ${hairLength} hair, professional appearance, suitable for dating app, no explicit content`;
  }
  
  async callStableDiffusion(prompt) {
    const response = await axios.post(this.stableDiffusionUrl, {
      prompt: prompt,
      negative_prompt: 'explicit, nude, inappropriate, adult content',
      steps: 20,
      width: 512,
      height: 512,
      guidance_scale: 7.5
    }, {
      headers: {
        'Authorization': `Bearer ${this.apiKey}`,
        'Content-Type': 'application/json'
      }
    });
    
    return response.data.images[0];
  }
  
  async downloadAndSave(url, filePath) {
    const response = await axios.get(url, { responseType: 'arraybuffer' });
    await fs.writeFile(filePath, response.data);
  }
}

module.exports = new AvatarService();
```

### Week 7-8: Enhanced Matching Algorithm

#### 4.1 Matching Service
```javascript
// src/services/matchingService.js
const { User, UserPreference } = require('../models');
const { Op } = require('sequelize');

class MatchingService {
  async findMatches(userId, filters = {}) {
    const user = await User.findByPk(userId, {
      include: [{ model: UserPreference, as: 'preferences' }]
    });
    
    if (!user) {
      throw new Error('User not found');
    }
    
    // Build query based on user preferences
    const whereClause = this.buildWhereClause(user, filters);
    
    const candidates = await User.findAll({
      where: whereClause,
      include: [{ model: UserPreference, as: 'preferences' }],
      limit: 50
    });
    
    // Calculate compatibility scores
    const matches = candidates
      .filter(candidate => candidate.id !== userId)
      .map(candidate => ({
        user: candidate,
        compatibilityScore: this.calculateCompatibility(user, candidate),
        stiCompatibility: this.checkSTICompatibility(user, candidate),
        distance: this.calculateDistance(user, candidate)
      }))
      .filter(match => match.compatibilityScore > 0.6)
      .sort((a, b) => b.compatibilityScore - a.compatibilityScore);
    
    return matches;
  }
  
  buildWhereClause(user, filters) {
    const where = {
      verified: true,
      profile_completed: true
    };
    
    // Gender preference matching
    if (user.preferences) {
      const genderPreferences = user.preferences.sexual_preferences?.gender_preferences || [];
      if (genderPreferences.length > 0) {
        where.gender = { [Op.in]: genderPreferences };
      }
    }
    
    // Age range
    const minAge = user.preferences?.age_range_min || 18;
    const maxAge = user.preferences?.age_range_max || 99;
    const minDate = new Date();
    const maxDate = new Date();
    minDate.setFullYear(minDate.getFullYear() - maxAge);
    maxDate.setFullYear(maxDate.getFullYear() - minAge);
    
    where.date_of_birth = {
      [Op.between]: [minDate, maxDate]
    };
    
    // Location-based filtering
    if (user.location_lat && user.location_lon) {
      const distance = user.preferences?.distance_preference || 50;
      const latRange = distance / 69; // Approximate miles to latitude degrees
      const lonRange = distance / (69 * Math.cos(user.location_lat * Math.PI / 180));
      
      where.location_lat = {
        [Op.between]: [user.location_lat - latRange, user.location_lat + latRange]
      };
      where.location_lon = {
        [Op.between]: [user.location_lon - lonRange, user.location_lon + lonRange]
      };
    }
    
    return where;
  }
  
  calculateCompatibility(user, candidate) {
    let score = 0;
    let factors = 0;
    
    // Basic compatibility factors
    if (user.gender === candidate.gender) score += 0.1;
    if (user.body_type === candidate.body_type) score += 0.1;
    if (user.ethnicity === candidate.ethnicity) score += 0.1;
    factors += 3;
    
    // Preference matching
    if (user.preferences && candidate.preferences) {
      // Relationship style compatibility
      if (user.preferences.relationship_style === candidate.preferences.relationship_style) {
        score += 0.2;
      }
      factors += 1;
      
      // Venue preferences
      const userVenues = user.preferences.venue_preferences || [];
      const candidateVenues = candidate.preferences.venue_preferences || [];
      const venueOverlap = userVenues.filter(v => candidateVenues.includes(v)).length;
      score += (venueOverlap / Math.max(userVenues.length, candidateVenues.length)) * 0.2;
      factors += 1;
    }
    
    return factors > 0 ? score / factors : 0;
  }
  
  checkSTICompatibility(user, candidate) {
    if (!user.preferences || !candidate.preferences) return true;
    
    const userSTI = user.preferences.sti_status || {};
    const candidateSTI = candidate.preferences.sti_status || {};
    
    // If both users are open about STI status and compatible
    if (userSTI.open_about_status && candidateSTI.open_about_status) {
      return true;
    }
    
    // If user has specific STI preferences
    if (userSTI.preferences) {
      return this.checkSTIPreferences(userSTI.preferences, candidateSTI);
    }
    
    return true;
  }
  
  calculateDistance(user, candidate) {
    if (!user.location_lat || !candidate.location_lat) return null;
    
    const R = 3959; // Earth's radius in miles
    const lat1 = user.location_lat * Math.PI / 180;
    const lat2 = candidate.location_lat * Math.PI / 180;
    const deltaLat = (candidate.location_lat - user.location_lat) * Math.PI / 180;
    const deltaLon = (candidate.location_lon - user.location_lon) * Math.PI / 180;
    
    const a = Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2) +
              Math.cos(lat1) * Math.cos(lat2) *
              Math.sin(deltaLon / 2) * Math.sin(deltaLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    
    return R * c;
  }
}

module.exports = new MatchingService();
```

## Phase 2: Location-Based Features (Weeks 9-16)

### Week 9-10: Location Service Implementation

#### 5.1 Location Service
```javascript
// src/services/locationService.js
const redis = require('redis');
const { User } = require('../models');

class LocationService {
  constructor() {
    this.redis = redis.createClient({
      url: process.env.REDIS_URL
    });
    this.redis.connect();
  }
  
  async announcePresence(userId, venueData) {
    const user = await User.findByPk(userId);
    if (!user) {
      throw new Error('User not found');
    }
    
    const presence = {
      userId,
      venueName: venueData.name,
      venueCoordinates: venueData.coordinates,
      userPreferences: venueData.preferences,
      privacyLevel: venueData.privacyLevel || 'venue-only',
      expiresAt: new Date(Date.now() + 4 * 60 * 60 * 1000), // 4 hours
      createdAt: new Date()
    };
    
    // Store in Redis with expiration
    const key = `presence:${userId}`;
    await this.redis.setEx(key, 14400, JSON.stringify(presence)); // 4 hours
    
    // Notify nearby users
    await this.notifyNearbyUsers(presence);
    
    return presence;
  }
  
  async getNearbyPresence(userId, radius = 5) {
    const user = await User.findByPk(userId);
    if (!user || !user.location_lat) {
      return [];
    }
    
    const latRange = radius / 69;
    const lonRange = radius / (69 * Math.cos(user.location_lat * Math.PI / 180));
    
    // Get all presence announcements
    const keys = await this.redis.keys('presence:*');
    const nearbyPresence = [];
    
    for (const key of keys) {
      const presenceData = await this.redis.get(key);
      if (presenceData) {
        const presence = JSON.parse(presenceData);
        
        // Check if within radius
        const distance = this.calculateDistance(
          user.location_lat, user.location_lon,
          presence.venueCoordinates.lat, presence.venueCoordinates.lon
        );
        
        if (distance <= radius) {
          nearbyPresence.push({
            ...presence,
            distance
          });
        }
      }
    }
    
    return nearbyPresence.sort((a, b) => a.distance - b.distance);
  }
  
  async removePresence(userId) {
    const key = `presence:${userId}`;
    await this.redis.del(key);
  }
  
  calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 3959; // Earth's radius in miles
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  }
  
  async notifyNearbyUsers(presence) {
    // Implementation for real-time notifications
    // This would integrate with WebSocket service
    console.log(`Notifying nearby users about presence at ${presence.venueName}`);
  }
}

module.exports = new LocationService();
```

## Phase 3: Security & Anti-Bot Measures (Weeks 17-20)

### Week 17-18: Security Implementation

#### 6.1 Security Service
```javascript
// src/services/securityService.js
const rateLimit = require('express-rate-limit');
const { User } = require('../models');

class SecurityService {
  // Rate limiting middleware
  createRateLimit() {
    return rateLimit({
      windowMs: 15 * 60 * 1000, // 15 minutes
      max: 100, // limit each IP to 100 requests per windowMs
      message: 'Too many requests from this IP, please try again later.',
      standardHeaders: true,
      legacyHeaders: false,
    });
  }
  
  // Phone verification
  async verifyPhone(userId, phoneNumber, verificationCode) {
    // Integration with SMS service (Twilio, etc.)
    const isValid = await this.validateSMSVerification(phoneNumber, verificationCode);
    
    if (isValid) {
      await User.update(
        { phone_verified: true },
        { where: { id: userId } }
      );
      return true;
    }
    
    return false;
  }
  
  // Photo verification
  async verifyPhoto(userId, photoData) {
    // AI-powered photo verification
    const verificationResult = await this.aiPhotoVerification(photoData);
    
    if (verificationResult.isValid) {
      await User.update(
        { photo_verified: true },
        { where: { id: userId } }
      );
      return true;
    }
    
    return false;
  }
  
  // Bot detection
  async detectBotBehavior(userId, action) {
    const user = await User.findByPk(userId);
    const behaviorPattern = {
      userId,
      action,
      timestamp: new Date(),
      ipAddress: action.ipAddress,
      userAgent: action.userAgent
    };
    
    // Analyze behavior patterns
    const botScore = await this.analyzeBehavior(behaviorPattern);
    
    if (botScore > 0.8) {
      await this.flagUser(userId, 'suspicious_bot_behavior');
      return false;
    }
    
    return true;
  }
  
  async analyzeBehavior(behaviorPattern) {
    // Implement machine learning model for bot detection
    // This would analyze patterns like:
    // - Message frequency
    // - Profile view patterns
    // - Click patterns
    // - Time-based behavior
    
    return 0.1; // Placeholder
  }
}

module.exports = new SecurityService();
```

## Next Steps

1. **Set up development environment** with the provided code structure
2. **Configure environment variables** for database, Redis, and AI services
3. **Implement the authentication flow** with the provided services
4. **Test the avatar generation** with Stable Diffusion or similar AI service
5. **Deploy to staging environment** for testing

## Environment Variables Needed

```bash
# Database
DATABASE_URL=mysql://user:password@localhost:3306/fwber
REDIS_URL=redis://localhost:6379

# JWT
JWT_SECRET=your-super-secret-jwt-key

# AI Services
STABLE_DIFFUSION_URL=https://api.stability.ai/v1/generation
STABLE_DIFFUSION_API_KEY=your-api-key

# SMS Service (for phone verification)
TWILIO_ACCOUNT_SID=your-twilio-sid
TWILIO_AUTH_TOKEN=your-twilio-token

# Email Service
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
```

This roadmap provides a solid foundation for modernizing your fwber.com project with current best practices and the enhanced features you requested.
