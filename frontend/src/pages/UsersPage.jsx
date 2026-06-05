import React, { useEffect, useState } from 'react'
import api from '../services/api'
import ErrorAlert from '../components/ErrorAlert'
import { useAuth } from '../context/AuthContext'

export default function UsersPage() {
  const { user: me }              = useAuth()
  const [users, setUsers]         = useState([])
  const [loading, setLoading]     = useState(true)
  const [error, setError]         = useState(null)
  const [showForm, setShowForm]   = useState(false)
  const [editing, setEditing]     = useState(null)
  const [saving, setSaving]       = useState(false)
  const [form, setForm] = useState({ name: '', email: '', password: '', role: 'staff', is_active: true })

  const load = () => {
    setLoading(true)
    api.get('/users').then(r => setUsers(r.data.data ?? [])).finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [])

  if (me?.role !== 'admin') {
    return <div className="card text-center py-12 text-gray-500">User management is available to admins only.</div>
  }

  const openCreate = () => { setEditing(null); setForm({ name: '', email: '', password: '', role: 'staff', is_active: true }); setShowForm(true) }
  const openEdit   = (u) => { setEditing(u); setForm({ name: u.name, email: u.email, password: '', role: u.role, is_active: u.is_active }); setShowForm(true) }

  const save = async (e) => {
    e.preventDefault()
    setSaving(true)
    setError(null)
    try {
      const payload = { ...form }
      if (!payload.password) delete payload.password
      if (editing) {
        await api.put(`/users/${editing.id}`, payload)
      } else {
        await api.post('/users', payload)
      }
      setShowForm(false)
      load()
    } catch (err) {
      setError(err)
    } finally {
      setSaving(false)
    }
  }

  const toggleActive = async (u) => {
    try {
      await api.post(`/users/${u.id}/toggle-active`)
      load()
    } catch (err) {
      setError(err)
    }
  }

  const remove = async (u) => {
    if (!confirm(`Delete user "${u.name}"?`)) return
    try {
      await api.delete(`/users/${u.id}`)
      load()
    } catch (err) {
      setError(err)
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Users</h1>
        <button className="btn-primary" onClick={openCreate}>+ New User</button>
      </div>

      <ErrorAlert error={error} />

      {showForm && (
        <div className="card space-y-4">
          <h2 className="font-semibold text-gray-900">{editing ? 'Edit User' : 'New User'}</h2>
          <form onSubmit={save} className="grid grid-cols-2 gap-4">
            <div><label className="label">Name</label><input className="input" value={form.name} onChange={e => setForm(p => ({ ...p, name: e.target.value }))} required /></div>
            <div><label className="label">Email</label><input type="email" className="input" value={form.email} onChange={e => setForm(p => ({ ...p, email: e.target.value }))} required /></div>
            <div>
              <label className="label">Password {editing ? '(leave blank to keep)' : ''}</label>
              <input type="password" className="input" value={form.password} onChange={e => setForm(p => ({ ...p, password: e.target.value }))} minLength={12} required={!editing} />
            </div>
            <div><label className="label">Role</label>
              <select className="input" value={form.role} onChange={e => setForm(p => ({ ...p, role: e.target.value }))}>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            {editing && (
              <div className="flex items-center gap-2 col-span-2">
                <input type="checkbox" id="is_active" checked={form.is_active} onChange={e => setForm(p => ({ ...p, is_active: e.target.checked }))} className="rounded" />
                <label htmlFor="is_active" className="text-sm">Active</label>
              </div>
            )}
            <div className="col-span-2 flex gap-2">
              <button type="submit" className="btn-primary" disabled={saving}>{saving ? 'Saving…' : 'Save'}</button>
              <button type="button" className="btn-secondary" onClick={() => setShowForm(false)}>Cancel</button>
            </div>
          </form>
        </div>
      )}

      {loading ? (
        <div className="py-12 text-center text-gray-400">Loading…</div>
      ) : (
        <div className="card p-0 overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                <th className="px-4 py-3 text-left font-medium text-gray-600">Email</th>
                <th className="px-4 py-3 text-left font-medium text-gray-600">Role</th>
                <th className="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {users.map(u => (
                <tr key={u.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-medium text-gray-900">{u.name}</td>
                  <td className="px-4 py-3 text-gray-600">{u.email}</td>
                  <td className="px-4 py-3 capitalize text-gray-600">{u.role}</td>
                  <td className="px-4 py-3">
                    {u.is_active ? <span className="badge-green">Active</span> : <span className="badge-red">Inactive</span>}
                  </td>
                  <td className="px-4 py-3 text-right space-x-2">
                    <button className="text-xs text-brand-600 hover:text-brand-800" onClick={() => openEdit(u)}>Edit</button>
                    <button className="text-xs text-yellow-600 hover:text-yellow-800" onClick={() => toggleActive(u)}>
                      {u.is_active ? 'Deactivate' : 'Activate'}
                    </button>
                    {u.id !== me?.id && (
                      <button className="text-xs text-red-500 hover:text-red-700" onClick={() => remove(u)}>Delete</button>
                    )}
                  </td>
                </tr>
              ))}
              {users.length === 0 && <tr><td colSpan={5} className="py-12 text-center text-gray-400">No users found</td></tr>}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
