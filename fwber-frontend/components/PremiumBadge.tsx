import React from 'react';
import { Star } from 'lucide-react';

export const PremiumBadge = () => {
  return (
    <div className="flex items-center gap-1 bg-gradient-to-r from-yellow-400 to-yellow-600 text-white px-2 py-1 rounded-full text-xs font-bold shadow-sm">
      <Star className="w-3 h-3 fill-current" />
      <span>GOLD</span>
    </div>
  );
};
