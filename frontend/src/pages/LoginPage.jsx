import React, { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import ErrorAlert from '../components/ErrorAlert'

export default function LoginPage() {
  const { login } = useAuth()
  const navigate   = useNavigate()

  const [form, setForm]     = useState({ email: '', password: '' })
  const [loading, setLoading] = useState(false)
  const [error, setError]   = useState(null)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      const data = await login(form.email, form.password)
      if (data.requires_otp) {
        sessionStorage.setItem('otp_masked_email', data.masked_email ?? '')
        navigate('/otp')
      }
    } catch (err) {
      setError(err)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-brand-950 to-brand-800 px-4">
      <div className="w-full max-w-md">
        {/* Logo */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/10 mb-4">
            <svg viewBox="0 0 40 40" className="w-10 h-10 text-white fill-current">
              <rect x="4" y="4" width="32" height="32" rx="6" fillOpacity="0.2"/>
              <path d="M12 14h16M12 20h10M12 26h12" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
            </svg>
          </div>
          <h1 className="text-2xl font-bold text-white">FeRa Clinic</h1>
          <p className="text-brand-200 text-sm mt-1">SMS Platform</p>
        </div>

        <div className="card">
          <h2 className="text-xl font-bold text-gray-900 mb-6">Sign in to your account</h2>

          <ErrorAlert error={error} />

          <form onSubmit={handleSubmit} className="space-y-4 mt-4">
            <div>
              <label className="label">Email address</label>
              <input
                type="email"
                className="input"
                placeholder="admin@feraclinic.com"
                value={form.email}
                onChange={e => setForm(p => ({ ...p, email: e.target.value }))}
                required
                autoFocus
              />
            </div>
            <div>
              <label className="label">Password</label>
              <input
                type="password"
                className="input"
                placeholder="••••••••••••"
                value={form.password}
                onChange={e => setForm(p => ({ ...p, password: e.target.value }))}
                required
              />
            </div>

            <div className="flex items-center justify-between text-sm">
              <label className="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" className="rounded border-gray-300 text-brand-600" />
                <span className="text-gray-600">Remember me</span>
              </label>
              <Link to="/forgot-password" className="text-brand-600 hover:text-brand-700 font-medium">
                Forgot password?
              </Link>
            </div>

            <button type="submit" className="btn-primary w-full py-2.5" disabled={loading}>
              {loading ? 'Signing in…' : 'Sign in'}
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}
