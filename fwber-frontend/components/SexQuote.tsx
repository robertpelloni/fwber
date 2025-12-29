"use client";

import { useState, useEffect } from 'react';
import { Quote } from 'lucide-react';

const QUOTES = [
  "Everything in the world is about sex except sex. Sex is about power. — Oscar Wilde",
  "The power of love is a curious thing / Make a one man weep, make another man sing. — Huey Lewis & The News",
  "Sex is the consolation you have when you can't have love. — Gabriel García Márquez",
  "The bridge between soul and body is sex. — Anais Nin",
  "Sex is emotion in motion. — Mae West",
  "Intimacy is the capacity to be rather weird with someone - and finding that that's ok with them. — Alain de Botton",
  "Sexuality is the most powerful force in the universe. — Unknown",
  "Sex is a part of nature. I go along with nature. — Marilyn Monroe",
  "The only way to get rid of a temptation is to yield to it. — Oscar Wilde",
  "Sexiness is a state of mind. — Priyanka Chopra",
  "I believe in long, slow, deep, soft, wet kisses that last three days. — Bull Durham",
  "Love is the answer, but while you're waiting for the answer, sex raises some pretty interesting questions. — Woody Allen",
  "Chemistry is you touching my arm and setting fire to my mind. — Nayyirah Waheed",
  "Sex is the art of controlling lack of control. — Paulo Coelho",
  "Sex is a conversation carried out by other means. — Peter Ustinov",
  "Sex is the nature of the human being. — Marlon Brando",
  "My mother told me to be a lady. And for her, that meant be your own person, be independent. — Ruth Bader Ginsburg",
  "Sex is one of the nine reasons for reincarnation. The other eight are unimportant. — Henry Miller",
  "Let's get it on. — Marvin Gaye",
  "Sexual healing is good for me. — Marvin Gaye",
  "You can leave your hat on. — Joe Cocker",
  "Like a virgin, touched for the very first time. — Madonna",
  "Touch-a, Touch-a, Touch-a, Touch Me — The Rocky Horror Picture Show",
  "Relax, don't do it, when you want to go to it. — Frankie Goes to Hollywood",
  "Physical, physical, I wanna get physical. — Olivia Newton-John"
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
    <div className="relative p-6 mb-8 rounded-2xl bg-[#2A0A4A] border border-[#FF0099]/30 shadow-[0_0_30px_rgba(255,0,153,0.3)] overflow-hidden group">
      {/* Ambient Glow Effects */}
      <div className="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-[#00F0FF]/30 to-[#FF0099]/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none animate-pulse"></div>
      <div className="absolute bottom-0 left-0 w-64 h-64 bg-gradient-to-tr from-[#FF0099]/20 to-[#00F0FF]/30 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2 pointer-events-none animate-pulse animation-delay-1000"></div>
      
      <div className="relative flex items-start gap-4 z-10">
        <div className="p-2 rounded-lg bg-[#2A0A4A]/80 border border-[#00F0FF]/50 backdrop-blur-sm shadow-[0_0_10px_rgba(0,240,255,0.2)]">
          <Quote className="h-5 w-5 text-[#00F0FF]" />
        </div>
        <p className="text-lg md:text-xl font-light leading-relaxed text-transparent bg-clip-text bg-gradient-to-r from-[#00F0FF] via-[#d4fafa] to-[#FF0099] italic tracking-wide drop-shadow-[0_0_2px_rgba(0,240,255,0.5)]">
          &ldquo;{quote}&rdquo;
        </p>
      </div>
    </div>
  );
}
