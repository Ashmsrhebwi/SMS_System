import React, { useEffect, useState } from 'react'
import api from '../services/api'
import ErrorAlert from '../components/ErrorAlert'

export default function TemplatesPage() {
  const [templates, setTemplates] = useState([])
  const [categories, setCategories] = useState([])
  const [loading, setLoading]     = useState(true)
  const [error, setError]         = useState(null)
  const [showForm, setShowForm]   = useState(false)
  const [editing, setEditing]     = useState(null)
  const [form, setForm] = useState({ name: '', content: '', category_id: '' })
  const [saving, setSaving] = useState(false)

  const load = () => {
    setLoading(true)
    api.get('/templates').then(r => {
      setTemplates(r.data.data?.data ?? [])
      setCategories(r.data.categories ?? [])
    }).finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [])

  const openCreate = () => { setEditing(null); setForm({ name: '', content: '', category_id: '' }); setShowForm(true) }
  const openEdit   = (t)  => { setEditing(t); setForm({ name: t.name, content: t.content, category_id: t.category_id ?? '' }); setShowForm(true) }

  const save = async (e) => {
    e.preventDefault()
    setSaving(true)
    setError(null)
    try {
      if (editing) {
        await api.put(`/templates/${editing.id}`, form)
      } else {
        await api.post('/templates', form)
      }
      setShowForm(false)
      load()
    } catch (err) {
      setError(err)
    } finally {
      setSaving(false)
    }
  }

  const remove = async (t) => {
    if (!confirm(`Delete template "${t.name}"?`)) return
    try {
      await api.delete(`/templates/${t.id}`)
      load()
    } catch (err) {
      setError(err)
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">SMS Templates</h1>
        <button className="btn-primary" onClick={openCreate}>+ New Template</button>
      </div>

      <ErrorAlert error={error} />

      {showForm && (
        <div className="card space-y-4">
          <h2 className="font-semibold text-gray-900">{editing ? 'Edit Template' : 'New Template'}</h2>
          <form onSubmit={save} className="space-y-4">
            <div><label className="label">Name</label>
              <input className="input" value={form.name} onChange={e => setForm(p => ({ ...p, name: e.target.value }))} required /></div>
            <div><label className="label">Category</label>
              <select className="input" value={form.category_id} onChange={e => setForm(p => ({ ...p, category_id: e.target.value }))}>
                <option value="">No category</option>
                {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select></div>
            <div><label className="label">Content</label>
              <textarea className="input min-h-[100px]" value={form.content} onChange={e => setForm(p => ({ ...p, content: e.target.value }))} required maxLength={1600} /></div>
            <div className="flex gap-2">
              <button type="submit" className="btn-primary" disabled={saving}>{saving ? 'Saving…' : 'Save'}</button>
              <button type="button" className="btn-secondary" onClick={() => setShowForm(false)}>Cancel</button>
            </div>
          </form>
        </div>
      )}

      {loading ? (
        <div className="py-12 text-center text-gray-400">Loading…</div>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {templates.map(t => (
            <div key={t.id} className="card space-y-2">
              <div className="flex items-start justify-between">
                <div>
                  <p className="font-medium text-gray-900">{t.name}</p>
                  {t.category && <span className="badge badge-blue text-xs mt-0.5">{t.category.name}</span>}
                </div>
                <div className="flex gap-1">
                  <button className="text-xs text-brand-600 hover:text-brand-800" onClick={() => openEdit(t)}>Edit</button>
                  <button className="text-xs text-red-500 hover:text-red-700 ml-1" onClick={() => remove(t)}>Delete</button>
                </div>
              </div>
              <p className="text-sm text-gray-600 line-clamp-3">{t.content}</p>
              <p className="text-xs text-gray-400">{t.content.length} chars · {Math.ceil(t.content.length / 160)} segment(s)</p>
            </div>
          ))}
          {templates.length === 0 && <p className="col-span-3 py-12 text-center text-gray-400">No templates yet</p>}
        </div>
      )}
    </div>
  )
}
