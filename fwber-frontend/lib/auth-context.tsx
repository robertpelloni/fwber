'use client'

import React, { createContext, useContext, useReducer, useEffect, ReactNode } from 'react'
import { logAuth, setUserContext, clearUserContext } from './logger'

// Types
interface User {
  id: number
  email: string
  name: string
  emailVerifiedAt: string | null
  createdAt: string
  updatedAt: string
  profile?: {
    displayName: string | null
    dateOfBirth: string | null
    gender: string | null
    pronouns: string | null
    sexualOrientation: string | null
    relationshipStyle: string | null
    bio: string | null
    locationLatitude: number | null
    locationLongitude: number | null
    locationDescription: string | null
    stiStatus: string | null
    preferences: Record<string, any> | null
    avatarUrl: string | null
    createdAt: string
    updatedAt: string
  }
}

interface AuthState {
  user: User | null
  token: string | null
  isLoading: boolean
  isAuthenticated: boolean
  error: string | null
}

type AuthAction =
  | { type: 'AUTH_START' }
  | { type: 'AUTH_SUCCESS'; payload: { user: User; token: string } }
  | { type: 'AUTH_FAILURE'; payload: string }
  | { type: 'LOGOUT' }
  | { type: 'CLEAR_ERROR' }
  | { type: 'UPDATE_USER'; payload: User }
  | { type: 'INITIALIZE_END' }

interface AuthContextType extends AuthState {
  login: (email: string, password: string) => Promise<void>
  register: (name: string, email: string, password: string, passwordConfirmation: string) => Promise<void>
  logout: () => void
  clearError: () => void
  updateUser: (user: User) => void
}

// Initial state
const initialState: AuthState = {
  user: null,
  token: null,
  isLoading: false,
  isAuthenticated: false,
  error: null,
}

// Reducer
function authReducer(state: AuthState, action: AuthAction): AuthState {
  switch (action.type) {
    case 'AUTH_START':
      return {
        ...state,
        isLoading: true,
        error: null,
      }
    case 'AUTH_SUCCESS':
      return {
        ...state,
        isLoading: false,
        isAuthenticated: true,
        user: action.payload.user,
        token: action.payload.token,
        error: null,
      }
    case 'AUTH_FAILURE':
      return {
        ...state,
        isLoading: false,
        isAuthenticated: false,
        user: null,
        token: null,
        error: action.payload,
      }
    case 'LOGOUT':
      return {
        ...state,
        isAuthenticated: false,
        user: null,
        token: null,
        error: null,
      }
    case 'CLEAR_ERROR':
      return {
        ...state,
        error: null,
      }
    case 'UPDATE_USER':
      return {
        ...state,
        user: action.payload,
      }
    case 'INITIALIZE_END':
      return {
        ...state,
        isLoading: false,
      }
    default:
      return state
  }
}

// Context
const AuthContext = createContext<AuthContextType | undefined>(undefined)

// Provider component
export function AuthProvider({ children }: { children: ReactNode }) {
  const [state, dispatch] = useReducer(authReducer, {
    ...initialState,
    isLoading: true,
  })

  // Initialize auth state from localStorage
  useEffect(() => {
    if (typeof window !== 'undefined') {
      const token = localStorage.getItem('fwber_token')
      const userStr = localStorage.getItem('fwber_user')
      const devToken = localStorage.getItem('auth_token')
      
      console.log('AuthContext Init:', { token, userStr, devToken }) // Debug log

      // Development bypass: if we have 'auth_token' = 'dev', treat as authenticated
      if (devToken === 'dev') {
        dispatch({ 
          type: 'AUTH_SUCCESS', 
          payload: { 
            user: { 
              id: 1, 
              email: 'dev@fwber.local', 
              name: 'Dev User',
              emailVerifiedAt: null,
              createdAt: new Date().toISOString(),
              updatedAt: new Date().toISOString()
            }, 
            token: devToken 
          } 
        })
        return
      }
      
      if (token && userStr) {
        try {
          const user = JSON.parse(userStr)
          console.log('AuthContext Restoring Session:', user) // Debug log
          dispatch({ type: 'AUTH_SUCCESS', payload: { user, token } })
          
          // Wrap side effects in try-catch so they don't break auth
          try {
            setUserContext(user)
            logAuth.sessionRestored(user.id)
          } catch (e) {
            console.error('Logging/Sentry error:', e)
          }
        } catch (error) {
          console.error('AuthContext Restore Error:', error) // Debug log
          // Clear invalid data
          localStorage.removeItem('fwber_token')
          localStorage.removeItem('fwber_user')
          dispatch({ type: 'INITIALIZE_END' })
        }
      } else {
        dispatch({ type: 'INITIALIZE_END' })
      }
    } else {
      dispatch({ type: 'INITIALIZE_END' })
    }
  }, [])

  // Save to localStorage when auth state changes
  useEffect(() => {
    if (state.isLoading) return

    if (typeof window !== 'undefined') {
      console.log('AuthContext Sync:', { isAuthenticated: state.isAuthenticated, token: state.token }) // Debug log
      if (state.isAuthenticated && state.token && state.user) {
        localStorage.setItem('fwber_token', state.token)
        localStorage.setItem('fwber_user', JSON.stringify(state.user))
      } else {
        localStorage.removeItem('fwber_token')
        localStorage.removeItem('fwber_user')
      }
    }
  }, [state.isAuthenticated, state.token, state.user, state.isLoading])

  // API base URL
  const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api'

  // Login function
  const login = async (email: string, password: string) => {
    dispatch({ type: 'AUTH_START' })
    
    try {
      const response = await fetch(`${API_BASE_URL}/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      })

      const data = await response.json()

      if (!response.ok) {
        logAuth.login(email, false, data.message)
        throw new Error(data.message || 'Login failed')
      }

      logAuth.login(email, true)
      setUserContext(data.user)
      dispatch({ 
        type: 'AUTH_SUCCESS', 
        payload: { 
          user: data.user, 
          token: data.token 
        } 
      })
    } catch (error) {
      dispatch({ 
        type: 'AUTH_FAILURE', 
        payload: error instanceof Error ? error.message : 'Login failed' 
      })
      throw error
    }
  }

  // Register function
  const register = async (name: string, email: string, password: string, passwordConfirmation: string) => {
    dispatch({ type: 'AUTH_START' })
    
    try {
      const response = await fetch(`${API_BASE_URL}/auth/register`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ 
          name, 
          email, 
          password, 
          password_confirmation: passwordConfirmation 
        }),
      })

      const data = await response.json()

      if (!response.ok) {
        logAuth.register(email, false, data.message)
        throw new Error(data.message || 'Registration failed')
      }

      logAuth.register(email, true)
      setUserContext(data.user)
      dispatch({ 
        type: 'AUTH_SUCCESS', 
        payload: { 
          user: data.user, 
          token: data.token 
        } 
      })
    } catch (error) {
      dispatch({ 
        type: 'AUTH_FAILURE', 
        payload: error instanceof Error ? error.message : 'Registration failed' 
      })
      throw error
    }
  }

  // Logout function
  const logout = () => {
    const userId = state.user?.id
    clearUserContext()
    logAuth.logout(userId)
    dispatch({ type: 'LOGOUT' })
  }

  // Clear error function
  const clearError = () => {
    dispatch({ type: 'CLEAR_ERROR' })
  }

  // Update user function
  const updateUser = (user: User) => {
    dispatch({ type: 'UPDATE_USER', payload: user })
  }

  const value: AuthContextType = {
    ...state,
    login,
    register,
    logout,
    clearError,
    updateUser,
  }

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  )
}

// Hook to use auth context
export function useAuth() {
  const context = useContext(AuthContext)
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return context
}
