import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../services/api'
import Pagination from '../components/Pagination'
import ErrorAlert from '../components/ErrorAlert'

export default function ContactsPage() {
  const [data, setData]       = useState(null)
  const [tags, setTags]       = useState([])
  const [page, setPage]       = useState(1)
  const [search, setSearch]   = useState('')
  const [tagFilter, setTagFilter] = useState('')
  const [optIn, setOptIn]     = useState('')
  const [loading, setLoading] = useState(true)
  const [importFile, setImportFile] = useState(null)
  const [importing, setImporting]   = useState(false)
  const [importResult, setImportResult] = useState(null)
  const [error, setError]     = useState(null)

  const load = (p = 1) => {
    setLoading(true)
    api.get('/contacts', { params: { page: p, search, tag: tagFilter, opt_in: optIn } })
      .then(r => setData(r.data))
      .finally(() => setLoading(false))
  }

  useEffect(() => { api.get('/tags').then(r => setTags(r.data.data ?? [])) }, [])
  useEffect(() => { load(1); setPage(1) }, [search, tagFilter, optIn])
  useEffect(() => { load(page) }, [page])

  const handleImport = async (e) => {
    e.preventDefault()
    if (!importFile) return
    setImporting(true)
    setError(null)
    const fd = new FormData()
    fd.append('file', importFile)
    fd.append('duplicate_action', 'skip')
    try {
      const res = await api.post('/contacts/import', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      setImportResult(res.data)
      load(1)
    } catch (err) {
      setError(err)
    } finally {
      setImporting(false)
      setImportFile(null)
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Contacts</h1>
        <div className="flex gap-2">
          <a href="/api/v1/contacts/export" target="_blank" rel="noreferrer" className="btn-secondary text-xs">
            Export
          </a>
          <Link to="/contacts/new" className="btn-primary">+ Add Contact</Link>
        </div>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap gap-2">
        <input
          className="input w-56"
          placeholder="Search name, phone, email…"
          value={search}
          onChange={e => setSearch(e.target.value)}
        />
        <select className="input w-40" value={tagFilter} onChange={e => setTagFilter(e.target.value)}>
          <option value="">All tags</option>
          {tags.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
        </select>
        <select className="input w-36" value={optIn} onChange={e => setOptIn(e.target.value)}>
          <option value="">All statuses</option>
          <option value="1">Opted in</option>
          <option value="0">Opted out</option>
        </select>
      </div>

      {/* Import */}
      <form onSubmit={handleImport} className="flex items-center gap-2 text-sm">
        <input type="file" accept=".xlsx,.xls,.csv" onChange={e => setImportFile(e.target.files[0])} className="text-xs" />
        <button type="submit" className="btn-secondary text-xs" disabled={!importFile || importing}>
          {importing ? 'Importing…' : 'Import'}
        </button>
      </form>

      <ErrorAlert error={error} />

      {importResult && (
        <div className="rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-800">
          Import complete: {importResult.imported} imported, {importResult.updated} updated, {importResult.skipped} skipped.
        </div>
      )}

      <div className="card p-0 overflow-hidden">
        {loading ? (
          <div className="py-12 text-center text-gray-400">Loading…</div>
        ) : (
          <>
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th className="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                  <th className="px-4 py-3 text-left font-medium text-gray-600">Phone</th>
                  <th className="px-4 py-3 text-left font-medium text-gray-600">Email</th>
                  <th className="px-4 py-3 text-left font-medium text-gray-600">Tags</th>
                  <th className="px-4 py-3 text-left font-medium text-gray-600">Opt-in</th>
                  <th className="px-4 py-3 text-left font-medium text-gray-600">Added</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {data?.data?.map(c => (
                  <tr key={c.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3">
                      <Link to={`/contacts/${c.id}`} className="font-medium text-gray-900 hover:text-brand-600">{c.name}</Link>
                    </td>
                    <td className="px-4 py-3 text-gray-600">{c.phone}</td>
                    <td className="px-4 py-3 text-gray-500">{c.email ?? '—'}</td>
                    <td className="px-4 py-3">
                      <div className="flex flex-wrap gap-1">
                        {c.tags?.map(t => (
                          <span key={t.id} className="badge badge-blue" style={t.color ? { backgroundColor: t.color + '22', color: t.color } : {}}>
                            {t.name}
                          </span>
                        ))}
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      {c.opted_in
                        ? <span className="badge-green">Opted in</span>
                        : <span className="badge-red">Opted out</span>}
                    </td>
                    <td className="px-4 py-3 text-gray-400">{new Date(c.created_at).toLocaleDateString()}</td>
                  </tr>
                ))}
                {data?.data?.length === 0 && (
                  <tr><td colSpan={6} className="py-12 text-center text-gray-400">No contacts found</td></tr>
                )}
              </tbody>
            </table>
            <Pagination meta={data?.meta} onPageChange={setPage} />
          </>
        )}
      </div>
    </div>
  )
}
