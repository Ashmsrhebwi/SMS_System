import React from 'react'
import { Link } from 'react-router-dom'

export default function NotFoundPage() {
  return (
    <div className="min-h-screen flex items-center justify-center">
      <div className="text-center">
        <p className="text-7xl font-bold text-gray-200">404</p>
        <p className="text-xl font-medium text-gray-700 mt-2">Page not found</p>
        <Link to="/" className="btn-primary mt-4 inline-block">Go to Dashboard</Link>
      </div>
    </div>
  )
}
