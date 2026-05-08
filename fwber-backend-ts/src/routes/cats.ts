import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// Seed data — famous internet cat memes with funny ratings
const cats = [
  { id: 1, name: 'Detroit Motor Kitty', image_url: 'https://placecats.com/millie/400/400', rating: 9.2, votes: 847, tagline: 'Purring like a V8 engine', breed: 'Domestic Shorthair' },
  { id: 2, name: 'Belle Isle Bengal', image_url: 'https://placecats.com/neo/400/400', rating: 8.8, votes: 623, tagline: 'Stalks the riverwalk at dawn', breed: 'Bengal' },
  { id: 3, name: 'Corktown Calico', image_url: 'https://placecats.com/lily/400/400', rating: 9.5, votes: 1203, tagline: 'Mayor of Michigan Avenue', breed: 'Calico' },
  { id: 4, name: 'Midtown Mitten', image_url: 'https://placecats.com/mica/400/400', rating: 7.9, votes: 412, tagline: 'Pays rent in purrs', breed: 'Tabby' },
  { id: 5, name: 'Eastern Market Orange', image_url: 'https://placecats.com/roo/400/400', rating: 9.8, votes: 2107, tagline: 'Has never missed a Saturday market', breed: 'Orange Tabby' },
  { id: 6, name: 'Hamtramck Hairball', image_url: 'https://placecats.com/jasper/400/400', rating: 8.1, votes: 331, tagline: 'Speaks three languages, meows in four', breed: 'Maine Coon mix' },
  { id: 7, name: 'Woodward Whiskers', image_url: 'https://placecats.com/boo/400/400', rating: 8.6, votes: 544, tagline: 'Cruising the avenue since 2019', breed: 'Siamese' },
  { id: 8, name: 'Renaissance Kitten', image_url: 'https://placecats.com/toby/400/400', rating: 9.1, votes: 789, tagline: 'Lives in the GM Renaissance Center lobby', breed: 'Persian' },
  { id: 9, name: '8 Mile Stray', image_url: 'https://placecats.com/bella/400/400', rating: 7.4, votes: 198, tagline: 'Survived two Michigan winters, fears nothing', breed: 'Mixed' },
  { id: 10, name: 'Lafayette Coney', image_url: 'https://placecats.com/rocky/400/400', rating: 8.3, votes: 467, tagline: 'Named after the coney island, not the other way', breed: 'Tuxedo' },
];

// GET /api/cats — list all cats with ratings
router.get('/', authenticate, (_req: any, res) => {
  res.json({
    data: cats.sort((a, b) => b.rating - a.rating),
    total: cats.length,
    message: 'Rate these Detroit cats!'
  });
});

// POST /api/cats/:id/vote — vote for a cat
router.post('/:id/vote', authenticate, (req: any, res) => {
  const catId = Number(req.params.id);
  const cat = cats.find(c => c.id === catId);
  if (!cat) return res.status(404).json({ error: 'Cat not found' });
  const score = Number(req.body.rating) || 5;
  // Simple running average
  cat.rating = Math.round(((cat.rating * cat.votes + score) / (cat.votes + 1)) * 10) / 10;
  cat.votes += 1;
  res.json({ success: true, cat: { id: cat.id, name: cat.name, rating: cat.rating, votes: cat.votes } });
});

export default router;
