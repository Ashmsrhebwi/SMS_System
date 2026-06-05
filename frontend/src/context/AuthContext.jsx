import React, { createContext, useContext, useState, useEffect, useCallback } from 'react'
import api from '../services/api'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem('auth_user'))
    } catch {
      return null
    }
  })
  const [loading, setLoading] = useState(false)

  const login = useCallback(async (email, password) => {
    const res = await api.post('/auth/login', { email, password })
    return res.data
  }, [])

  const verifyOtp = useCallback(async (otp) => {
    const res = await api.post('/auth/otp/verify', { otp })
    const { token, user: userData } = res.data
    localStorage.setItem('sanctum_token', token)
    localStorage.setItem('auth_user', JSON.stringify(userData))
    setUser(userData)
    return userData
  }, [])

  const resendOtp = useCallback(async () => {
    const res = await api.post('/auth/otp/resend')
    return res.data
  }, [])

  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout')
    } catch {
      // ignore
    }
    localStorage.removeItem('sanctum_token')
    localStorage.removeItem('auth_user')
    setUser(null)
  }, [])

  const refreshUser = useCallback(async () => {
    try {
      const res = await api.get('/auth/me')
      const userData = res.data.user
      localStorage.setItem('auth_user', JSON.stringify(userData))
      setUser(userData)
    } catch {
      setUser(null)
    }
  }, [])

  return (
    <AuthContext.Provider value={{ user, loading, login, verifyOtp, resendOtp, logout, refreshUser }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
