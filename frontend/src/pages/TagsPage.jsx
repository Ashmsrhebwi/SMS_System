import React, { useEffect, useState } from 'react'
import api from '../services/api'
import ErrorAlert from '../components/ErrorAlert'

export default function TagsPage() {
  const [tags, setTags]       = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError]     = useState(null)
  const [form, setForm]       = useState({ name: '', color: '#6366f1' })
  const [editing, setEditing] = useState(null)
  const [saving, setSaving]   = useState(false)

  const load = () => {
    setLoading(true)
    api.get('/tags').then(r => setTags(r.data.data ?? [])).finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [])

  const save = async (e) => {
    e.preventDefault()
    setSaving(true)
    setError(null)
    try {
      if (editing) {
        await api.put(`/tags/${editing.id}`, form)
      } else {
        await api.post('/tags', form)
      }
      setEditing(null)
      setForm({ name: '', color: '#6366f1' })
      load()
    } catch (err) {
      setError(err)
    } finally {
      setSaving(false)
    }
  }

  const remove = async (t) => {
    if (!confirm(`Delete tag "${t.name}"? This removes it from all contacts.`)) return
    try {
      await api.delete(`/tags/${t.id}`)
      load()
    } catch (err) {
      setError(err)
    }
  }

  return (
    <div className="max-w-xl space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Tags</h1>
      <ErrorAlert error={error} />

      <div className="card space-y-4">
        <h2 className="font-semibold text-gray-900">{editing ? 'Edit Tag' : 'New Tag'}</h2>
        <form onSubmit={save} className="flex gap-2 items-end">
          <div className="flex-1">
            <label className="label">Name</label>
            <input className="input" value={form.name} onChange={e => setForm(p => ({ ...p, name: e.target.value }))} required />
          </div>
          <div>
            <label className="label">Color</label>
            <input type="color" className="h-9 w-16 rounded border border-gray-300 p-0.5 cursor-pointer" value={form.color ?? '#6366f1'}
              onChange={e => setForm(p => ({ ...p, color: e.target.value }))} />
          </div>
          <button type="submit" className="btn-primary" disabled={saving}>{saving ? 'Saving…' : editing ? 'Update' : 'Add'}</button>
          {editing && <button type="button" className="btn-secondary" onClick={() => { setEditing(null); setForm({ name: '', color: '#6366f1' }) }}>Cancel</button>}
        </form>
      </div>

      {loading ? (
        <div className="py-8 text-center text-gray-400">Loading…</div>
      ) : (
        <div className="card p-0 overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-4 py-3 text-left font-medium text-gray-600">Tag</th>
                <th className="px-4 py-3 text-left font-medium text-gray-600">Contacts</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {tags.map(t => (
                <tr key={t.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3">
                    <span className="badge" style={{ backgroundColor: (t.color ?? '#6366f1') + '22', color: t.color ?? '#6366f1' }}>
                      {t.name}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-gray-600">{t.contacts_count ?? 0}</td>
                  <td className="px-4 py-3 text-right">
                    <button className="text-xs text-brand-600 hover:text-brand-800" onClick={() => { setEditing(t); setForm({ name: t.name, color: t.color ?? '#6366f1' }) }}>Edit</button>
                    <button className="text-xs text-red-500 hover:text-red-700 ml-3" onClick={() => remove(t)}>Delete</button>
                  </td>
                </tr>
              ))}
              {tags.length === 0 && <tr><td colSpan={3} className="py-8 text-center text-gray-400">No tags yet</td></tr>}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
