import React, { useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Bell, LogOut, Moon, Sun, Menu, ChevronDown, User } from 'lucide-react'
import { motion, AnimatePresence } from 'framer-motion'
import { useTheme } from '../../context/ThemeContext'
import { useAuth } from '../../context/AuthContext'
import { useToast } from '../../context/ToastContext'
import { Avatar } from '../ui/Avatar'
import { cn } from '../../lib/cn'

export function TopBar({ onMobileMenuOpen }) {
  const { isDark, toggleTheme } = useTheme()
  const { user, logout }        = useAuth()
  const { toast }               = useToast()
  const navigate                = useNavigate()
  const [menuOpen, setMenuOpen] = useState(false)
  const menuRef                 = useRef(null)

  const handleLogout = async () => {
    setMenuOpen(false)
    await logout()
    toast.success('Signed out successfully')
    navigate('/login')
  }

  return (
    <header className="h-[60px] flex items-center justify-between px-4 md:px-6 border-b border-[var(--border)] bg-[var(--surface)] shrink-0">
      {/* Mobile menu trigger */}
      <button
        onClick={onMobileMenuOpen}
        className="lg:hidden p-2 -ml-2 rounded-lg text-[var(--text-secondary)] hover:bg-[var(--surface-2)] hover:text-[var(--text-primary)] transition-colors"
      >
        <Menu size={18} />
      </button>

      {/* Spacer */}
      <div className="flex-1" />

      {/* Right controls */}
      <div className="flex items-center gap-1">
        {/* Theme toggle */}
        <button
          onClick={toggleTheme}
          className="p-2 rounded-lg text-[var(--text-secondary)] hover:bg-[var(--surface-2)] hover:text-[var(--text-primary)] transition-colors"
          title={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
        >
          <motion.div
            key={isDark ? 'moon' : 'sun'}
            initial={{ rotate: -20, opacity: 0 }}
            animate={{ rotate: 0, opacity: 1 }}
            transition={{ duration: 0.15 }}
          >
            {isDark ? <Sun size={16} /> : <Moon size={16} />}
          </motion.div>
        </button>

        {/* User menu */}
        <div className="relative" ref={menuRef}>
          <button
            onClick={() => setMenuOpen(v => !v)}
            className={cn(
              'flex items-center gap-2 rounded-lg pl-2 pr-2.5 py-1.5',
              'text-[var(--text-secondary)] hover:bg-[var(--surface-2)] transition-colors',
              menuOpen && 'bg-[var(--surface-2)]'
            )}
          >
            <Avatar name={user?.name} size="xs" />
            <span className="hidden sm:block text-sm font-medium text-[var(--text-primary)] max-w-[120px] truncate">
              {user?.name}
            </span>
            <ChevronDown size={12} className={cn('transition-transform', menuOpen && 'rotate-180')} />
          </button>

          <AnimatePresence>
            {menuOpen && (
              <>
                <div className="fixed inset-0 z-10" onClick={() => setMenuOpen(false)} />
                <motion.div
                  className={cn(
                    'absolute right-0 top-full mt-1.5 z-20 w-52',
                    'bg-[var(--surface)] rounded-xl border border-[var(--border)] shadow-[var(--shadow-xl)]',
                    'overflow-hidden'
                  )}
                  initial={{ opacity: 0, y: -6, scale: 0.96 }}
                  animate={{ opacity: 1, y: 0, scale: 1 }}
                  exit={{ opacity: 0, y: -6, scale: 0.96 }}
                  transition={{ duration: 0.12 }}
                >
                  <div className="px-3 py-2.5 border-b border-[var(--border)]">
                    <p className="text-sm font-medium text-[var(--text-primary)]">{user?.name}</p>
                    <p className="text-xs text-[var(--text-tertiary)] truncate">{user?.email}</p>
                  </div>
                  <div className="p-1">
                    <button
                      onClick={handleLogout}
                      className="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg transition-colors"
                    >
                      <LogOut size={14} />
                      Sign out
                    </button>
                  </div>
                </motion.div>
              </>
            )}
          </AnimatePresence>
        </div>
      </div>
    </header>
  )
}
