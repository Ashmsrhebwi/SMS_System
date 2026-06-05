import React from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider, useAuth } from './context/AuthContext'
import AppLayout from './components/AppLayout'

import LoginPage from './pages/LoginPage'
import OtpPage from './pages/OtpPage'
import ForgotPasswordPage from './pages/ForgotPasswordPage'
import ResetPasswordPage from './pages/ResetPasswordPage'
import DashboardPage from './pages/DashboardPage'
import CampaignsPage from './pages/CampaignsPage'
import CampaignDetailPage from './pages/CampaignDetailPage'
import CampaignCreatePage from './pages/CampaignCreatePage'
import ContactsPage from './pages/ContactsPage'
import ContactDetailPage from './pages/ContactDetailPage'
import TemplatesPage from './pages/TemplatesPage'
import TagsPage from './pages/TagsPage'
import SegmentsPage from './pages/SegmentsPage'
import ReportsPage from './pages/ReportsPage'
import UsersPage from './pages/UsersPage'
import NotFoundPage from './pages/NotFoundPage'

function RequireAuth({ children }) {
  const { user } = useAuth()
  if (!user) return <Navigate to="/login" replace />
  return children
}

function RequireGuest({ children }) {
  const { user } = useAuth()
  if (user) return <Navigate to="/" replace />
  return children
}

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter basename="/spa">
        <Routes>
          {/* Guest routes */}
          <Route path="/login" element={<RequireGuest><LoginPage /></RequireGuest>} />
          <Route path="/otp" element={<OtpPage />} />
          <Route path="/forgot-password" element={<RequireGuest><ForgotPasswordPage /></RequireGuest>} />
          <Route path="/reset-password" element={<RequireGuest><ResetPasswordPage /></RequireGuest>} />

          {/* Authenticated routes */}
          <Route element={<RequireAuth><AppLayout /></RequireAuth>}>
            <Route index element={<DashboardPage />} />
            <Route path="campaigns" element={<CampaignsPage />} />
            <Route path="campaigns/new" element={<CampaignCreatePage />} />
            <Route path="campaigns/:id" element={<CampaignDetailPage />} />
            <Route path="contacts" element={<ContactsPage />} />
            <Route path="contacts/:id" element={<ContactDetailPage />} />
            <Route path="templates" element={<TemplatesPage />} />
            <Route path="tags" element={<TagsPage />} />
            <Route path="segments" element={<SegmentsPage />} />
            <Route path="reports" element={<ReportsPage />} />
            <Route path="users" element={<UsersPage />} />
          </Route>

          <Route path="*" element={<NotFoundPage />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  )
}
