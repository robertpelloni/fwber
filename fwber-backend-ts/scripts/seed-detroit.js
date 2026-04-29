/**
 * Seed 100 randomized profiles in the Detroit metro area.
 * Run: node scripts/seed-detroit.js
 */

const mysql = require('mysql2/promise');
const crypto = require('crypto');

const DB = {
  host: '127.0.0.1',
  port: 3306,
  user: 'fwber',
  password: 'Temppass.0!',
  database: 'fwber_production',
};

// ─── Data pools ────────────────────────────────────────────────────────────

const FIRST_NAMES_F = ['Aaliyah','Ava','Bella','Brooklyn','Camille','Chloe','Destiny','Diamond','Elena','Emma','Faith','Grace','Hailey','Imani','Jada','Jasmine','Kayla','Kylie','Laila','Luna','Madison','Maya','Mia','Naomi','Nia','Olivia','Paris','Queen','Riley','Sage','Samira','Skyler','Taylor','Trinity','Unique','Victoria','Winter','Xena','Yara','Zuri'];
const FIRST_NAMES_M = ['Aaron','Andre','Antonio','Brandon','Brian','Carlos','Carter','Darnell','DeAndre','Devin','Dre','Elijah','Eric','Gavin','Hassan','Isaiah','Jaden','Jalen','Jamal','Jaylen','Jerome','Jordan','Kareem','Khalil','Lamar','Malik','Marcus','Marquis','Nasir','Omari','Quincy','Rashad','Sean','Terrell','Tyrone','Victor','Walter','Xavier','Yusuf','Zion'];
const LAST_NAMES = ['Adams','Allen','Anderson','Baker','Barnes','Bell','Bennett','Brooks','Brown','Butler','Campbell','Carter','Clark','Coleman','Collins','Cooper','Cox','Davis','Dixon','Edwards','Evans','Fisher','Foster','Franklin','Freeman','Garcia','Gibson','Gonzales','Graham','Grant','Gray','Green','Hall','Hamilton','Harris','Harrison','Hayes','Henderson','Hill','Holland','Holmes','Howard','Hudson','Hughes','Jackson','James','Jenkins','Johnson','Jones','Jordan','Kelly','Kennedy','Kim','King','Knight','Lee','Lewis','Long','Lopez','Malone','Martin','Mason','Matthews','McCoy','McDonald','Miller','Mitchell','Moore','Morgan','Morris','Murray','Nelson','Parker','Patel','Patterson','Payne','Perry','Peterson','Phillips','Pierce','Porter','Powell','Price','Quinn','Reed','Richardson','Rivera','Robinson','Rodriguez','Rogers','Ross','Russell','Scott','Simmons','Smith','Stevens','Stone','Sullivan','Taylor','Thomas','Thompson','Turner','Walker','Wallace','Ward','Washington','Watson','Webb','Wells','West','White','Williams','Wilson','Wood','Wright','Young'];
const BIOS = [
  'Just moved to the D. Looking to meet new people and explore the city.',
  'Motor City native. Love the downtown scene and Eastern Market weekends.',
  'Work hard, play harder. Detroit sports fan. Let\'s grab a drink.',
  'Creative soul looking for genuine connections in the metro area.',
  'Born and raised in Michigan. Outdoor enthusiast when it\'s not freezing.',
  'Tech professional by day, foodie by night. Belle Isle is my happy place.',
  'Just living life one day at a time. Love Detroit\'s comeback story.',
  'Coffee addict. Dog lover. Always down for a Tigers game.',
  'New to Michigan. Show me around the best spots in the city!',
  'Artist and musician. The Detroit art scene is underrated.',
  'Fitness enthusiast looking for someone to explore the Riverwalk with.',
  'Lions fan (yes, really). Let\'s tailgate and see where things go.',
  'Food truck hunter. Always looking for the next great spot.',
  'Quiet nights in or nights out on the town — I\'m easy going.',
  'Photographer looking for my muse. Detroit architecture is my passion.',
  'Grad student at Wayne State. Looking for someone to study with… or not.',
  'Love cooking, hiking, and deep conversations at 2am.',
  'Automotive engineer. Yes, I actually work in the auto industry here.',
  'Small business owner. Supporting local everything.',
  'Let\'s skip the small talk and get to the good stuff.',
  'Detroit Renaissance Center is my backyard. City living at its finest.',
  'Music lover — techno, hip-hop, jazz. Movement Festival is Christmas.',
  'Yoga instructor and meditation guide. Seeking positive energy.',
  'I can probably beat you at bowling. Royal Oak lanes, any time.',
  'Cocktail enthusiast. I know every speakeasy in Midtown.',
  'Night owl. Daytime is overrated. Who\'s up for late night adventures?',
  'Single parent doing my best. Looking for someone who gets it.',
  'Recent grad starting fresh in Detroit. The future is bright.',
  'Gamer by night, accountant by day. Looking for player 2.',
  'Marine vet. Detroit is home now. Looking for something real.',
];
const GENDERS = ['male','female','non-binary'];
const ORIENTATIONS = ['straight','gay','lesbian','bisexual','pansexual','queer'];
const RELATIONSHIP_STYLES = ['monogamous','non-monogamous','open','polyamorous',''];
const BODY_TYPES = ['slim','athletic','average','curvy','muscular','plus_size','dad_bod'];
const HAIR_COLORS = ['black','brown','blonde','red','gray','bald','other'];
const EYE_COLORS = ['brown','blue','green','hazel','gray','amber'];
const EDUCATION = ['high_school','some_college','associates','bachelors','masters','phd'];
const OCCUPATIONS = ['Software Engineer','Nurse','Teacher','Marketing Manager','Chef','Electrician','Realtor','Photographer','Accountant','Graphic Designer','Mechanic','Social Worker','Barber','Sales Manager','Physical Therapist','Journalist','Architect','Pharmacist','Firefighter','Personal Trainer','Lawyer','Dentist','Veterinarian','Police Officer','Librarian','Bartender','Uber Driver','College Student','Retail Manager','Contractor'];
const INTERESTS = ['reading','hiking','cooking','gaming','music','movies','sports','travel','photography','art','fitness','dancing','yoga','craft_beer','wine','podcasts','gardening','camping','fishing','skiing','board_games','comedy','karaoke','volunteering','writing','fashion','tech','cars','diy','pets'];
const LOOKING_FOR = ['dating','casual','friendship','relationship','networking'];

// Detroit metro area coordinates (roughly)
const LOCATIONS = [
  { city: 'Detroit', state: 'MI', lat: 42.3314, lng: -83.0458 },
  { city: 'Royal Oak', state: 'MI', lat: 42.4895, lng: -83.1446 },
  { city: 'Ferndale', state: 'MI', lat: 42.4606, lng: -83.1348 },
  { city: 'Ann Arbor', state: 'MI', lat: 42.2808, lng: -83.7430 },
  { city: 'Dearborn', state: 'MI', lat: 42.3223, lng: -83.1763 },
  { city: 'Troy', state: 'MI', lat: 42.6064, lng: -83.1498 },
  { city: 'Southfield', state: 'MI', lat: 42.4734, lng: -83.2219 },
  { city: 'Warren', state: 'MI', lat: 42.5145, lng: -83.0147 },
  { city: 'Sterling Heights', state: 'MI', lat: 42.5803, lng: -83.0300 },
  { city: 'Livonia', state: 'MI', lat: 42.3684, lng: -83.3527 },
  { city: 'Farmington Hills', state: 'MI', lat: 42.4867, lng: -83.3736 },
  { city: 'Birmingham', state: 'MI', lat: 42.5456, lng: -83.2108 },
  { city: 'Grosse Pointe', state: 'MI', lat: 42.3853, lng: -82.9046 },
  { city: 'Pontiac', state: 'MI', lat: 42.6389, lng: -83.2911 },
  { city: 'Ypsilanti', state: 'MI', lat: 42.2406, lng: -83.6130 },
];

// ─── Helpers ───────────────────────────────────────────────────────────────

function pick(arr) { return arr[Math.floor(Math.random() * arr.length)]; }
function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }
function shuffle(arr) { const a = [...arr]; for (let i = a.length - 1; i > 0; i--) { const j = randInt(0, i); [a[i], a[j]] = [a[j], a[i]]; } return a; }

function randomDateOfBirth() {
  const age = randInt(20, 45);
  const year = new Date().getFullYear() - age;
  const month = randInt(1, 12);
  const day = randInt(1, 28);
  return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

function randomLat(base) { return +(base + (Math.random() - 0.5) * 0.05).toFixed(7); }
function randomLng(base) { return +(base + (Math.random() - 0.5) * 0.05).toFixed(7); }

function randomPreferences(gender) {
  const hobbies = shuffle(INTERESTS).slice(0, randInt(3, 8));
  return JSON.stringify({
    smoking: pick(['non-smoker', 'occasional', 'regular', 'social', 'vaping', '']),
    drinking: pick(['non-drinker', 'occasional', 'regular', 'social', 'sober', '']),
    exercise: pick(['daily', 'several-times-week', 'weekly', 'occasional', 'rarely', '']),
    diet: pick(['omnivore', 'vegetarian', 'vegan', 'other', '']),
    occupation: pick(OCCUPATIONS),
    education: pick(EDUCATION),
    hobbies,
    age_range_min: randInt(20, 30),
    age_range_max: randInt(35, 55),
  });
}

function randomLookingFor() {
  const all = shuffle(LOOKING_FOR);
  return JSON.stringify(all.slice(0, randInt(1, 3)));
}

// Simple password hash (bcrypt substitute for seed — just SHA256)
function hashPassword(pw) {
  return '$2a$12$' + crypto.createHash('sha256').update(pw + 'fwber-seed-salt').digest('hex').slice(0, 53);
}

// ─── Main ──────────────────────────────────────────────────────────────────

async function main() {
  const conn = await mysql.createConnection(DB);
  console.log('Connected to database.');

  const COUNT = 100;
  const created = [];

  for (let i = 0; i < COUNT; i++) {
    const isFemale = Math.random() > 0.5;
    const firstName = pick(isFemale ? FIRST_NAMES_F : FIRST_NAMES_M);
    const lastName = pick(LAST_NAMES);
    const gender = isFemale ? 'female' : pick(GENDERS);
    const displayName = `${firstName} ${lastName.charAt(0)}.`;
    const email = `${firstName.toLowerCase()}.${lastName.toLowerCase()}${randInt(1, 999)}@seed.fwber.local`;
    const loc = pick(LOCATIONS);
    const lat = randomLat(loc.lat);
    const lng = randomLng(loc.lng);

    // Create user
    const [userResult] = await conn.execute(
      `INSERT INTO users (name, email, password, role, token_balance, current_streak, golden_tickets_remaining, is_active, tier, created_at, updated_at, last_active_at, last_seen_at)
       VALUES (?, ?, ?, 'user', 0.00, 0, 3, 1, 'free', NOW(), NOW(), NOW(), NOW())`,
      [displayName, email, hashPassword('password123')]
    );
    const userId = userResult.insertId;

    // Create profile
    const dob = randomDateOfBirth();
    const orientation = pick(ORIENTATIONS);
    const bodyType = pick(BODY_TYPES);
    const hairColor = pick(HAIR_COLORS);
    const eyeColor = pick(EYE_COLORS);
    const height = randInt(150, 198);
    const bio = pick(BIOS);
    const preferences = randomPreferences(gender);
    const lookingFor = randomLookingFor();
    const interests = JSON.stringify(shuffle(INTERESTS).slice(0, randInt(3, 8)));
    const occupation = pick(OCCUPATIONS);
    const education = pick(EDUCATION);
    const religion = pick(['christian', 'catholic', 'atheist', 'agnostic', 'spiritual', 'muslim', '']);
    const politics = pick(['liberal', 'moderate', 'conservative', 'apolitical', '']);
    const drinking = pick(['non-drinker', 'occasional', 'regular', 'social', 'sober', '']);
    const smoking = pick(['non-smoker', 'occasional', 'regular', 'social', 'vaping', '']);
    const cannabis = pick(['never', 'occasional', 'regular', 'social', 'medical', '']);
    const locationDesc = `${loc.city}, ${loc.state}`;

    await conn.execute(
      `INSERT INTO user_profiles (
        user_id, display_name, date_of_birth, birthdate, gender, sexual_orientation,
        bio, height_cm, body_type, hair_color, eye_color, ethnicity,
        occupation, education, religion, political_views,
        drinking_status, smoking_status, cannabis_status,
        location_latitude, location_longitude, location_description,
        preferences, looking_for, interests,
        is_verified, created_at, updated_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())`,
      [
        userId, displayName, dob, dob, gender, orientation,
        bio, height, bodyType, hairColor, eyeColor, pick(['white', 'black', 'hispanic_latino', 'asian', 'middle_eastern', 'mixed']),
        occupation, education, religion, politics,
        drinking, smoking, cannabis,
        lat, lng, locationDesc,
        preferences, lookingFor, interests,
        Math.random() > 0.7 ? 1 : 0
      ]
    );

    created.push({ userId, displayName, location: locationDesc });
    if ((i + 1) % 20 === 0) console.log(`Created ${i + 1}/${COUNT} profiles...`);
  }

  console.log(`\n✅ Done! Created ${COUNT} seed profiles in the Detroit metro area.`);
  console.log('\nSample profiles:');
  created.slice(0, 5).forEach(p => console.log(`  #${p.userId} ${p.displayName} — ${p.location}`));

  await conn.end();
}

main().catch(err => { console.error('Seed failed:', err); process.exit(1); });
