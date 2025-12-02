import React from 'react';

interface LogoProps {
  className?: string;
  showDotMe?: boolean;
}

export function Logo({ className = '', showDotMe = true }: LogoProps) {
  const baseLetterClass = "text-transparent bg-clip-text [-webkit-text-stroke:1px_black] md:[-webkit-text-stroke:2px_black] [paint-order:stroke_fill] drop-shadow-[2px_2px_0px_rgba(0,0,0,0.2)] md:drop-shadow-[4px_4px_0px_rgba(0,0,0,0.2)]";
  
  return (
    <div className={`font-black tracking-tight flex items-baseline select-none ${className}`}>
      <span className={`${baseLetterClass} bg-gradient-to-b from-pink-500 to-pink-700`}>F</span>
      <span className={`${baseLetterClass} bg-gradient-to-b from-green-400 to-green-600`}>W</span>
      <span className={`${baseLetterClass} bg-gradient-to-b from-cyan-400 to-blue-600`}>B</span>
      <span className={`${baseLetterClass} bg-gradient-to-b from-purple-500 to-purple-700 relative`}>
        er
      </span>
      {showDotMe && (
        <span className="text-gray-400 font-bold text-[0.5em] ml-0.5 drop-shadow-none [-webkit-text-stroke:0]">
          .me
        </span>
      )}
    </div>
  );
}
