"use client";

import { useState, useEffect } from 'react';
import { Quote } from 'lucide-react';

const QUOTES = [
  "Everything in the world is about sex except sex. Sex is about power. — Oscar Wilde",
  "I must, I must, I must increase my bust — Lord Of Acid",
  "The power of love is a curious thing / Make a one man weep, make another man sing / Change a hawk to a little white dove / More than a feeling; that's the power of love — Huey Lewis & The News",
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
  "Every body gets horny.",
  "Sex is a part of nature. I go along with nature. — Marilyn Monroe",
  "I don't know the question, but sex is definitely the answer. — Woody Allen",
  "Sex is like air; it's not important unless you aren't getting any. — John Callahan",
  "The only way to get rid of a temptation is to yield to it. — Oscar Wilde",
  "Sexiness is a state of mind. — Priyanka Chopra",
  "I believe in long, slow, deep, soft, wet kisses that last three days. — Bull Durham",
  "Sex is the most fun you can have without laughing. — Woody Allen",
  "Love is the answer, but while you're waiting for the answer, sex raises some pretty interesting questions. — Woody Allen",
  "Is it dirty? Only if it's done right. — Woody Allen",
  "Chemistry is you touching my arm and setting fire to my mind. — Nayyirah Waheed",
  "Sex is the art of controlling lack of control. — Paulo Coelho",
  "Sex is a conversation carried out by other means. — Peter Ustinov",
  "I need sex for a clear complexion, but I'd rather do it for love. — Joan Crawford",
  "Sex is the nature of the human being. — Marlon Brando",
  "A liberated woman is one who has sex before marriage and a job after. — Gloria Steinem",
  "Sex: the thing that takes up the least amount of time and causes the most amount of trouble. — John Barrymore",
  "Biologically speaking, if something bites you it's more likely to be female. — Desmond Morris",
  "I've been on so many blind dates, I should get a free dog. — Wendy Liebman",
  "My mother told me to be a lady. And for her, that meant be your own person, be independent. — Ruth Bader Ginsburg",
  "Sex is one of the nine reasons for reincarnation. The other eight are unimportant. — Henry Miller",
  "I want your sex. — George Michael",
  "Let's get it on. — Marvin Gaye",
  "Sexual healing is good for me. — Marvin Gaye",
  "You can leave your hat on. — Joe Cocker",
  "I'm too sexy for my shirt. — Right Said Fred",
  "Like a virgin, touched for the very first time. — Madonna",
  "I wanna sex you up. — Color Me Badd",
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
    <div className="relative p-6 mb-8 rounded-2xl bg-slate-950 border border-slate-800 shadow-[0_0_40px_-10px_rgba(124,58,237,0.15)] overflow-hidden group">
      {/* Ambient Glow Effects */}
      <div className="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-purple-500/10 to-blue-500/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
      <div className="absolute bottom-0 left-0 w-64 h-64 bg-gradient-to-tr from-pink-500/10 to-orange-500/10 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2 pointer-events-none"></div>
      
      <div className="relative flex items-start gap-4 z-10">
        <div className="p-2 rounded-lg bg-slate-900/50 border border-slate-800 backdrop-blur-sm">
          <Quote className="h-5 w-5 text-slate-400" />
        </div>
        <p className="text-lg md:text-xl font-light leading-relaxed text-transparent bg-clip-text bg-gradient-to-r from-slate-100 via-slate-300 to-slate-400 italic tracking-wide">
          &ldquo;{quote}&rdquo;
        </p>
      </div>
    </div>
  );
}
