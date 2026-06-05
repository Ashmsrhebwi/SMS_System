import React, { useState } from 'react'
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

const NAV = [
  { to: '/',          label: 'Dashboard',  icon: '📊' },
  { to: '/campaigns', label: 'Campaigns',  icon: '📢' },
  { to: '/contacts',  label: 'Contacts',   icon: '👥' },
  { to: '/templates', label: 'Templates',  icon: '📝' },
  { to: '/tags',      label: 'Tags',       icon: '🏷️' },
  { to: '/segments',  label: 'Segments',   icon: '🎯' },
  { to: '/reports',   label: 'Reports',    icon: '📈' },
]

const ADMIN_NAV = [
  { to: '/users', label: 'Users', icon: '👤' },
]

export default function AppLayout() {
  const { user, logout } = useAuth()
  const navigate         = useNavigate()
  const [sidebarOpen, setSidebarOpen] = useState(false)

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const navLinkClass = ({ isActive }) =>
    `flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
      isActive
        ? 'bg-brand-50 text-brand-700'
        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
    }`

  const Sidebar = () => (
    <div className="flex h-full flex-col bg-white border-r border-gray-200">
      {/* Logo */}
      <div className="flex h-16 items-center px-4 border-b border-gray-200">
        <span className="text-xl font-bold text-brand-700">FeRa Clinic</span>
        <span className="ml-2 text-xs text-gray-500 font-medium">SMS</span>
      </div>

      {/* Nav */}
      <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        {NAV.map(({ to, label, icon }) => (
          <NavLink key={to} to={to} end={to === '/'} className={navLinkClass}>
            <span>{icon}</span>
            <span>{label}</span>
          </NavLink>
        ))}

        {user?.role === 'admin' && (
          <>
            <hr className="my-3 border-gray-200" />
            {ADMIN_NAV.map(({ to, label, icon }) => (
              <NavLink key={to} to={to} className={navLinkClass}>
                <span>{icon}</span>
                <span>{label}</span>
              </NavLink>
            ))}
          </>
        )}
      </nav>

      {/* User section */}
      <div className="border-t border-gray-200 p-4">
        <div className="flex items-center gap-3">
          <div className="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-brand-700 text-sm font-bold">
            {user?.name?.[0]?.toUpperCase()}
          </div>
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium text-gray-900 truncate">{user?.name}</p>
            <p className="text-xs text-gray-500 capitalize">{user?.role}</p>
          </div>
          <button onClick={handleLogout} className="text-gray-400 hover:text-gray-600 text-xs" title="Logout">
            ⏏️
          </button>
        </div>
      </div>
    </div>
  )

  return (
    <div className="flex h-screen overflow-hidden">
      {/* Desktop sidebar */}
      <div className="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0">
        <Sidebar />
      </div>

      {/* Mobile sidebar overlay */}
      {sidebarOpen && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <div className="absolute inset-0 bg-black/50" onClick={() => setSidebarOpen(false)} />
          <div className="relative flex flex-col w-64 h-full">
            <Sidebar />
          </div>
        </div>
      )}

      {/* Main */}
      <div className="flex flex-1 flex-col lg:pl-64 overflow-hidden">
        {/* Mobile topbar */}
        <div className="sticky top-0 z-10 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 lg:hidden">
          <button onClick={() => setSidebarOpen(true)} className="text-gray-600">
            ☰
          </button>
          <span className="font-bold text-brand-700">FeRa Clinic SMS</span>
        </div>

        {/* Page content */}
        <main className="flex-1 overflow-y-auto p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
