import React from 'react';

interface LogoProps {
  className?: string;
  showDotMe?: boolean;
}

export function Logo({ className = '', showDotMe = true }: LogoProps) {
  const letters = [
    { char: 'F', gradient: 'from-pink-500 to-pink-700' },
    { char: 'W', gradient: 'from-green-400 to-green-600' },
    { char: 'B', gradient: 'from-cyan-400 to-blue-600' },
    { char: 'er', gradient: 'from-purple-500 to-purple-700' },
  ];

  // Common font classes to ensure perfect alignment between layers
  const fontClasses = "font-black tracking-tight";
  
  // Stroke width for the outline layer
  // Using a thicker stroke on the background layer creates the outline effect
  // 2px stroke -> 1px visible outline (since half is inside)
  // 4px stroke -> 2px visible outline
  const outlineClass = "text-black [-webkit-text-stroke:3px_black] md:[-webkit-text-stroke:5px_black]";
  
  // Drop shadow applied to the outline layer
  const dropShadowClass = "drop-shadow-[2px_2px_0px_rgba(0,0,0,0.2)] md:drop-shadow-[4px_4px_0px_rgba(0,0,0,0.2)]";

  return (
    <div className={`relative inline-block ${className}`}>
      {/* Layer 1: Outline (Background) 
          This layer renders the text in solid black with a thick stroke.
          Because it's solid black, overlapping characters merge into a single silhouette,
          fixing the "janky overlapping outlines" issue.
      */}
      <div 
        className={`${fontClasses} flex items-baseline absolute inset-0 select-none ${dropShadowClass} z-0`} 
        aria-hidden="true"
      >
        {letters.map((l, i) => (
          <span key={i} className={outlineClass}>
            {l.char}
          </span>
        ))}
      </div>

      {/* Layer 2: Fill (Foreground) 
          This layer renders the colorful gradients.
          It sits exactly on top of the outline layer.
      */}
      <div className={`${fontClasses} flex items-baseline relative z-10 select-none animate-hue-cycle animate-glow`}>
        {letters.map((l, i) => (
          <span 
            key={i} 
            className={`text-transparent bg-clip-text bg-gradient-to-b ${l.gradient}`}
          >
            {l.char}
          </span>
        ))}
        
        {showDotMe && (
          <span className="text-gray-400 font-bold text-[0.5em] ml-0.5">
            .me
          </span>
        )}
      </div>
    </div>
  );
}
