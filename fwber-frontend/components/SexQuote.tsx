"use client";

import { useState, useEffect } from 'react';
import { Quote } from 'lucide-react';

const QUOTES = [
  "Everything is about sex, except sex. Sex is about power. — Oscar Wilde",
  "Let's talk about sex, baby. Let's talk about you and me. — Salt-N-Pepa",
  "Sex is the consolation you have when you can't have love. — Gabriel García Márquez",
  "Sex without love is a meaningless experience, but as far as meaningless experiences go its pretty damn good. — Woody Allen",
  "The bridge between soul and body is sex. — Anais Nin",
  "Sex is emotion in motion. — Mae West",
  "Intimacy is the capacity to be rather weird with someone - and finding that that's ok with them. — Alain de Botton",
  "Sexuality is the most powerful force in the universe. — Unknown",
  "Good sex is like good bridge. If you don't have a good partner, you'd better have a good hand. — Mae West",
  "Even the Queen of England was a slut in heat. — Unknown",
  "Everybody wants to get laid.",
  "Every body likes sex.",
  "Every body gets horny."
];

export default function SexQuote() {
  const [quote, setQuote] = useState<string>("");

  useEffect(() => {
    // Select a random quote on mount (client-side only to avoid hydration mismatch)
    const random = QUOTES[Math.floor(Math.random() * QUOTES.length)];
    setQuote(random);
  }, []);

  if (!quote) return null;

  return (
    <div className="bg-orange-50 border-l-4 border-orange-500 p-4 mb-6 rounded-r-lg shadow-sm">
      <div className="flex items-start gap-3">
        <Quote className="h-6 w-6 text-orange-400 flex-shrink-0" />
        <p className="text-orange-800 italic font-medium text-lg leading-relaxed">
          "{quote}"
        </p>
      </div>
    </div>
  );
}
