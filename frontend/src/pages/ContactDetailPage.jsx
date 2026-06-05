import React, { useEffect, useState } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { ArrowLeft, Phone, Mail, Calendar, Tag, MessageSquare, Trash2, Edit2, Send, StickyNote, Plus } from 'lucide-react'
import api from '../services/api'
import { useToast } from '../context/ToastContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card, CardHeader } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input } from '../components/ui/Input'
import { Badge, StatusBadge } from '../components/ui/Badge'
import { Avatar } from '../components/ui/Avatar'
import { Skeleton } from '../components/ui/Skeleton'
import { staggerContainer, staggerItem } from '../lib/animations'

function InfoRow({ icon: Icon, label, value }) {
  if (!value) return null
  return (
    <div className="flex items-start gap-3 py-3 border-b border-[var(--border)] last:border-0">
      <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[var(--surface-2)] text-[var(--text-tertiary)] mt-0.5">
        <Icon size={13} />
      </div>
      <div>
        <p className="text-xs text-[var(--text-tertiary)] mb-0.5">{label}</p>
        <p className="text-sm text-[var(--text-primary)]">{value}</p>
      </div>
    </div>
  )
}

export default function ContactDetailPage() {
  const { id }     = useParams()
  const navigate   = useNavigate()
  const { toast }  = useToast()

  const [contact, setContact]   = useState(null)
  const [loading, setLoading]   = useState(true)
  const [note, setNote]         = useState('')
  const [savingNote, setSavingNote] = useState(false)
  const [toggling, setToggling] = useState(false)

  const load = () => {
    setLoading(true)
    api.get(`/contacts/${id}`)
      .then(r => setContact(r.data.data))
      .catch(() => toast.error('Failed to load contact'))
      .finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [id])

  const toggleOptIn = async () => {
    setToggling(true)
    try {
      const res = await api.post(`/contacts/${id}/toggle-opt-in`)
      setContact(p => ({ ...p, opted_in: res.data.opted_in }))
      toast.success(res.data.opted_in ? 'Contact opted in' : 'Contact opted out')
    } catch {
      toast.error('Action failed')
    } finally {
      setToggling(false)
    }
  }

  const handleDelete = async () => {
    if (!confirm('Delete this contact? This cannot be undone.')) return
    try {
      await api.delete(`/contacts/${id}`)
      toast.success('Contact deleted')
      navigate('/contacts')
    } catch {
      toast.error('Delete failed')
    }
  }

  const saveNote = async (e) => {
    e.preventDefault()
    if (!note.trim()) return
    setSavingNote(true)
    try {
      await api.post(`/contacts/${id}/notes`, { note })
      setNote('')
      load()
    } catch {
      toast.error('Failed to save note')
    } finally {
      setSavingNote(false)
    }
  }

  if (loading) {
    return (
      <div className="max-w-3xl space-y-6">
        <Skeleton className="h-8 w-48 rounded-lg" />
        <div className="rounded-xl bg-[var(--surface)] border border-[var(--border)] p-6">
          <Skeleton className="h-16 w-16 rounded-full mb-4" />
          <Skeleton className="h-6 w-40 mb-2 rounded" />
          <Skeleton className="h-4 w-24 rounded" />
        </div>
      </div>
    )
  }

  if (!contact) return null

  return (
    <div className="max-w-3xl space-y-6">
      <PageHeader
        title={contact.name}
        subtitle={contact.phone}
        breadcrumbs={[{ label: 'Contacts', href: '/contacts' }, { label: contact.name }]}
        action={
          <div className="flex items-center gap-2">
            <Button
              variant="secondary"
              size="sm"
              loading={toggling}
              onClick={toggleOptIn}
            >
              {contact.opted_in ? 'Opt Out' : 'Opt In'}
            </Button>
            <Button
              variant="danger"
              size="sm"
              leftIcon={<Trash2 size={13} />}
              onClick={handleDelete}
            >
              Delete
            </Button>
          </div>
        }
      />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Left: profile card */}
        <div className="lg:col-span-1 space-y-4">
          <Card>
            <div className="flex flex-col items-center text-center pb-4 mb-4 border-b border-[var(--border)]">
              <Avatar name={contact.name} size="xl" className="mb-3" />
              <h2 className="font-bold text-base text-[var(--text-primary)]">{contact.name}</h2>
              <StatusBadge status={contact.opted_in ? 'opted_in' : 'opted_out'} className="mt-1" />
            </div>

            <div className="space-y-0">
              <InfoRow icon={Phone}    label="Phone"   value={contact.phone} />
              <InfoRow icon={Mail}     label="Email"   value={contact.email} />
              <InfoRow icon={Calendar} label="Added"   value={new Date(contact.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })} />
            </div>

            {contact.tags?.length > 0 && (
              <div className="mt-4 pt-4 border-t border-[var(--border)]">
                <p className="text-xs text-[var(--text-tertiary)] mb-2 flex items-center gap-1.5"><Tag size={11} /> Tags</p>
                <div className="flex flex-wrap gap-1.5">
                  {contact.tags.map(t => (
                    <Badge key={t.id} variant="brand" size="sm"
                      style={{ background: (t.color ?? '#6366f1') + '22', color: t.color ?? '#6366f1', borderColor: (t.color ?? '#6366f1') + '44' }}>
                      {t.name}
                    </Badge>
                  ))}
                </div>
              </div>
            )}
          </Card>

          {/* Quick stats */}
          {contact.messages_count !== undefined && (
            <Card>
              <CardHeader title="Activity" />
              <div className="grid grid-cols-2 gap-3">
                {[
                  { label: 'Messages', value: contact.messages_count ?? 0, icon: MessageSquare },
                  { label: 'Notes',    value: contact.contact_notes?.length ?? 0, icon: StickyNote },
                ].map(s => (
                  <div key={s.label} className="rounded-lg bg-[var(--surface-2)] p-3 text-center">
                    <p className="text-xl font-bold text-[var(--text-primary)] tabular">{s.value}</p>
                    <p className="text-xs text-[var(--text-tertiary)] mt-0.5">{s.label}</p>
                  </div>
                ))}
              </div>
            </Card>
          )}
        </div>

        {/* Right: notes */}
        <div className="lg:col-span-2">
          <Card>
            <CardHeader title="Notes" subtitle="Internal team notes" />
            <form onSubmit={saveNote} className="flex gap-2 mb-5">
              <Input
                placeholder="Add a note…"
                value={note}
                onChange={e => setNote(e.target.value)}
                className="flex-1"
              />
              <Button type="submit" size="sm" loading={savingNote} leftIcon={<Plus size={12} />}>
                Add
              </Button>
            </form>

            {contact.contact_notes?.length ? (
              <motion.div
                className="space-y-3"
                variants={staggerContainer}
                initial="initial"
                animate="animate"
              >
                {contact.contact_notes.map((n, i) => (
                  <motion.div
                    key={n.id}
                    variants={staggerItem}
                    className="rounded-xl bg-[var(--surface-2)] border border-[var(--border)] p-4"
                  >
                    <p className="text-sm text-[var(--text-primary)] leading-relaxed">{n.note}</p>
                    <p className="text-xs text-[var(--text-tertiary)] mt-2 flex items-center gap-1.5">
                      <span className="font-medium">{n.author?.name ?? 'Unknown'}</span>
                      <span>·</span>
                      <span>{new Date(n.created_at).toLocaleString()}</span>
                    </p>
                  </motion.div>
                ))}
              </motion.div>
            ) : (
              <div className="py-8 text-center">
                <p className="text-sm text-[var(--text-tertiary)]">No notes yet. Add one above.</p>
              </div>
            )}
          </Card>
        </div>
      </div>
    </div>
  )
}
