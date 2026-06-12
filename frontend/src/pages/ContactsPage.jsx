import React, { useCallback, useEffect, useRef, useState } from 'react'
import { motion } from 'framer-motion'
import { Search, Upload, Download, Plus, Users, Filter } from 'lucide-react'
import api from '../services/api'
import { useToast } from '../context/ToastContext'
import { ContactDrawer } from '../features/contacts/ContactDrawer'
import { PageHeader } from '../components/layout/PageHeader'
import { Card } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input, Select } from '../components/ui/Input'
import { Badge, StatusBadge } from '../components/ui/Badge'
import { Pagination } from '../components/ui/Pagination'
import { SkeletonTable } from '../components/ui/Skeleton'
import { EmptyState } from '../components/ui/EmptyState'
import { Avatar } from '../components/ui/Avatar'
import { cn } from '../lib/cn'

export default function ContactsPage() {
  const { toast }               = useToast()
  const [data, setData]         = useState(null)
  const [tags, setTags]         = useState([])
  const [page, setPage]         = useState(1)
  const [search, setSearch]     = useState('')
  const [tagFilter, setTag]     = useState('')
  const [optIn, setOptIn]       = useState('')
  const [loading, setLoading]   = useState(true)
  const [drawerContact, setDrawer] = useState(null)
  const [importing, setImporting]  = useState(false)
  const fileInputRef = useRef(null)

  const load = useCallback((p = 1) => {
    setLoading(true)
    api.get('/contacts', { params: { page: p, search, tag: tagFilter, opt_in: optIn } })
      .then(r => setData(r.data))
      .finally(() => setLoading(false))
  }, [search, tagFilter, optIn])

  useEffect(() => { api.get('/tags').then(r => setTags(r.data.data ?? [])) }, [])
  useEffect(() => { load(1); setPage(1) }, [search, tagFilter, optIn])
  useEffect(() => { if (page > 1) load(page) }, [page])

  const handleImport = async (e) => {
    const file = e.target.files?.[0]
    if (!file) return
    setImporting(true)
    const fd = new FormData()
    fd.append('file', file)
    fd.append('duplicate_action', 'skip')
    try {
      const res = await api.post('/contacts/import', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      toast.success(`Imported ${res.data.imported} · Updated ${res.data.updated} · Skipped ${res.data.skipped}`)
      load(1)
    } catch {
      toast.error('Import failed. Check your file format.')
    } finally {
      setImporting(false)
      e.target.value = ''
    }
  }

  return (
    <div className="space-y-5">
      <PageHeader
        title="Contacts"
        subtitle={`${data?.meta?.total ?? 0} contacts`}
        action={
          <div className="flex items-center gap-2">
            <Button variant="secondary" leftIcon={<Upload size={14} />} loading={importing} onClick={() => fileInputRef.current?.click()}>
              Import
            </Button>
            <input ref={fileInputRef} type="file" accept=".xlsx,.xls,.csv" className="sr-only" onChange={handleImport} />
            <a href="/api/v1/contacts/export" target="_blank" rel="noreferrer">
              <Button variant="secondary" leftIcon={<Download size={14} />}>Export</Button>
            </a>
          </div>
        }
      />

      {/* Filters */}
      <div className="flex flex-col sm:flex-row gap-3">
        <Input
          placeholder="Search name, phone, email…"
          leftIcon={<Search size={14} />}
          value={search}
          onChange={e => setSearch(e.target.value)}
          className="sm:w-72"
        />
        <Select value={tagFilter} onChange={e => setTag(e.target.value)} className="sm:w-40">
          <option value="">All tags</option>
          {tags.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
        </Select>
        <Select value={optIn} onChange={e => setOptIn(e.target.value)} className="sm:w-36">
          <option value="">All statuses</option>
          <option value="1">Opted in</option>
          <option value="0">Opted out</option>
        </Select>
      </div>

      {loading ? (
        <SkeletonTable rows={8} />
      ) : !data?.data?.length ? (
        <Card>
          <EmptyState
            icon={Users}
            title="No contacts found"
            description="Import contacts or adjust your filters."
          />
        </Card>
      ) : (
        <Card padding={false} className="overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                {['Contact', 'Phone', 'Email', 'Tags', 'Status', 'Added'].map(h => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--border)]">
              {data.data.map((c, i) => (
                <motion.tr
                  key={c.id}
                  className="table-row-hover"
                  onClick={() => setDrawer(c.id)}
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ delay: i * 0.02 }}
                >
                  <td className="px-4 py-3.5">
                    <div className="flex items-center gap-3">
                      <Avatar name={c.name} size="sm" />
                      <span className="text-sm font-medium text-[var(--text-primary)]">{c.name}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3.5 text-sm text-[var(--text-secondary)]">{c.phone}</td>
                  <td className="px-4 py-3.5 text-sm text-[var(--text-secondary)]">{c.email ?? '—'}</td>
                  <td className="px-4 py-3.5">
                    <div className="flex flex-wrap gap-1">
                      {c.tags?.slice(0, 2).map(t => (
                        <Badge key={t.id} variant="brand" size="sm">{t.name}</Badge>
                      ))}
                      {c.tags?.length > 2 && (
                        <Badge variant="default" size="sm">+{c.tags.length - 2}</Badge>
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3.5">
                    <StatusBadge status={c.opted_in ? 'opted_in' : 'opted_out'} />
                  </td>
                  <td className="px-4 py-3.5 text-xs text-[var(--text-tertiary)]">
                    {new Date(c.created_at).toLocaleDateString()}
                  </td>
                </motion.tr>
              ))}
            </tbody>
          </table>
          <Pagination meta={data?.meta} onPageChange={setPage} />
        </Card>
      )}

      <ContactDrawer
        contactId={drawerContact}
        open={!!drawerContact}
        onClose={() => setDrawer(null)}
        onUpdate={() => load(page)}
      />
    </div>
  )
}
