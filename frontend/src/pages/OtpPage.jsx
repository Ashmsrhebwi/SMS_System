import React, { useState, useRef, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import ErrorAlert from '../components/ErrorAlert'

export default function OtpPage() {
  const { verifyOtp, resendOtp } = useAuth()
  const navigate = useNavigate()

  const maskedEmail = sessionStorage.getItem('otp_masked_email') ?? 'your email'
  const [digits, setDigits]     = useState(Array(6).fill(''))
  const [loading, setLoading]   = useState(false)
  const [resending, setResending] = useState(false)
  const [error, setError]       = useState(null)
  const [success, setSuccess]   = useState('')
  const inputs = useRef([])

  useEffect(() => {
    inputs.current[0]?.focus()
  }, [])

  const handleChange = (i, val) => {
    if (!/^\d?$/.test(val)) return
    const next = [...digits]
    next[i] = val
    setDigits(next)
    if (val && i < 5) inputs.current[i + 1]?.focus()
  }

  const handleKeyDown = (i, e) => {
    if (e.key === 'Backspace' && !digits[i] && i > 0) {
      inputs.current[i - 1]?.focus()
    }
  }

  const handlePaste = (e) => {
    const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
    if (pasted.length === 6) {
      setDigits(pasted.split(''))
      inputs.current[5]?.focus()
    }
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    const otp = digits.join('')
    if (otp.length < 6) return
    setError(null)
    setLoading(true)
    try {
      await verifyOtp(otp)
      navigate('/', { replace: true })
    } catch (err) {
      setError(err)
      setDigits(Array(6).fill(''))
      inputs.current[0]?.focus()
    } finally {
      setLoading(false)
    }
  }

  const handleResend = async () => {
    setResending(true)
    setError(null)
    setSuccess('')
    try {
      await resendOtp()
      setSuccess('A new code has been sent to your email.')
      setDigits(Array(6).fill(''))
      inputs.current[0]?.focus()
    } catch (err) {
      setError(err)
    } finally {
      setResending(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-brand-950 to-brand-800 px-4">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/10 mb-4">
            <span className="text-3xl">🔐</span>
          </div>
          <h1 className="text-2xl font-bold text-white">Two-Factor Authentication</h1>
          <p className="text-brand-200 text-sm mt-1">Enter the 6-digit code sent to {maskedEmail}</p>
        </div>

        <div className="card">
          <div className="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800 mb-4">
            Code expires in 5 minutes · Maximum 3 attempts
          </div>

          <ErrorAlert error={error} />
          {success && <div className="rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-800 mb-2">{success}</div>}

          <form onSubmit={handleSubmit} className="mt-4 space-y-6">
            <div className="flex justify-center gap-2" onPaste={handlePaste}>
              {digits.map((d, i) => (
                <input
                  key={i}
                  ref={el => inputs.current[i] = el}
                  type="text"
                  inputMode="numeric"
                  maxLength={1}
                  value={d}
                  onChange={e => handleChange(i, e.target.value)}
                  onKeyDown={e => handleKeyDown(i, e)}
                  className="w-12 h-14 text-center text-2xl font-bold rounded-lg border-2 border-gray-300 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                />
              ))}
            </div>

            <button
              type="submit"
              className="btn-primary w-full py-2.5"
              disabled={loading || digits.join('').length < 6}
            >
              {loading ? 'Verifying…' : 'Verify Code'}
            </button>
          </form>

          <div className="mt-4 text-center text-sm text-gray-600">
            Didn't receive a code?{' '}
            <button
              onClick={handleResend}
              disabled={resending}
              className="text-brand-600 hover:text-brand-700 font-medium disabled:opacity-50"
            >
              {resending ? 'Sending…' : 'Resend'}
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
