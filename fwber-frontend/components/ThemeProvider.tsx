"use client"

import * as React from "react"
import { ThemeProvider as NextThemesProvider } from "next-themes"

export type ThemeStyle = 'classic' | 'speakeasy' | 'neon' | 'clean'

interface ThemeContextType {
  themeStyle: ThemeStyle
  setThemeStyle: (style: ThemeStyle) => void
}

const ThemeContext = React.createContext<ThemeContextType | undefined>(undefined)

export function useThemeStyle() {
  const context = React.useContext(ThemeContext)
  if (!context) {
    throw new Error('useThemeStyle must be used within a ThemeProvider')
  }
  return context
}

export function ThemeProvider({ children, ...props }: React.ComponentProps<typeof NextThemesProvider>) {
  const [themeStyle, setThemeStyle] = React.useState<ThemeStyle>('classic')

  React.useEffect(() => {
    // Load saved theme style
    const savedStyle = localStorage.getItem('theme-style') as ThemeStyle
    if (savedStyle) {
      setThemeStyle(savedStyle)
    }
  }, [])

  const handleSetThemeStyle = (style: ThemeStyle) => {
    setThemeStyle(style)
    localStorage.setItem('theme-style', style)
    // Apply data attribute to html element
    document.documentElement.setAttribute('data-theme-style', style)
  }

  // Apply initial style
  React.useEffect(() => {
    document.documentElement.setAttribute('data-theme-style', themeStyle)
  }, [themeStyle])

  return (
    <ThemeContext.Provider value={{ themeStyle, setThemeStyle: handleSetThemeStyle }}>
      <NextThemesProvider {...props}>
        {children}
      </NextThemesProvider>
    </ThemeContext.Provider>
  )
}
