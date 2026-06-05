import React, { useEffect, useState } from 'react'
import { motion } from 'framer-motion'
import { Filter, Plus, Trash2, Users, Tag, CheckCircle2, XCircle, ChevronDown, ChevronUp } from 'lucide-react'
import api from '../services/api'
import { useToast } from '../context/ToastContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input, Select } from '../components/ui/Input'
import { Badge } from '../components/ui/Badge'
import { Modal } from '../components/ui/Modal'
import { EmptyState } from '../components/ui/EmptyState'
import { Skeleton } from '../components/ui/Skeleton'

const RULE_FIELDS = [
  { value: 'opted_in',    label: 'Opt-in status' },
  { value: 'tag',         label: 'Tag' },
  { value: 'country',     label: 'Country' },
  { value: 'city',        label: 'City' },
  { value: 'created_at',  label: 'Created date' },
]

const OPERATORS = {
  opted_in:   [{ value: 'is', label: 'is' }],
  tag:        [{ value: 'has', label: 'has tag' }, { value: 'not_has', label: 'does not have tag' }],
  country:    [{ value: 'is', label: 'is' }, { value: 'is_not', label: 'is not' }],
  city:       [{ value: 'is', label: 'is' }, { value: 'contains', label: 'contains' }],
  created_at: [{ value: 'before', label: 'before' }, { value: 'after', label: 'after' }, { value: 'within_days', label: 'within last N days' }],
}

function RuleRow({ rule, index, tags, onChange, onRemove }) {
  const ops = OPERATORS[rule.field] ?? [{ value: 'is', label: 'is' }]

  return (
    <div className="flex items-center gap-2 flex-wrap">
      {index > 0 && (
        <span className="text-xs font-semibold text-brand-600 bg-brand-50 dark:bg-brand-950 dark:text-brand-400 px-2 py-0.5 rounded-full shrink-0">AND</span>
      )}
      <Select value={rule.field} onChange={e => onChange({ ...rule, field: e.target.value, operator: '', value: '' })} className="flex-1 min-w-[120px]">
        {RULE_FIELDS.map(f => <option key={f.value} value={f.value}>{f.label}</option>)}
      </Select>
      <Select value={rule.operator} onChange={e => onChange({ ...rule, operator: e.target.value })} className="flex-1 min-w-[120px]">
        <option value="">operator</option>
        {ops.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
      </Select>

      {rule.field === 'opted_in' && (
        <Select value={rule.value} onChange={e => onChange({ ...rule, value: e.target.value })} className="flex-1 min-w-[100px]">
          <option value="">select</option>
          <option value="1">Opted in</option>
          <option value="0">Opted out</option>
        </Select>
      )}
      {rule.field === 'tag' && (
        <Select value={rule.value} onChange={e => onChange({ ...rule, value: e.target.value })} className="flex-1 min-w-[120px]">
          <option value="">select tag</option>
          {tags.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
        </Select>
      )}
      {['country', 'city'].includes(rule.field) && (
        <Input value={rule.value} onChange={e => onChange({ ...rule, value: e.target.value })} placeholder="value" className="flex-1 min-w-[120px]" />
      )}
      {rule.field === 'created_at' && (
        rule.operator === 'within_days'
          ? <Input type="number" value={rule.value} onChange={e => onChange({ ...rule, value: e.target.value })} placeholder="days" className="flex-1 min-w-[80px]" />
          : <Input type="date" value={rule.value} onChange={e => onChange({ ...rule, value: e.target.value })} className="flex-1 min-w-[140px]" />
      )}

      <button onClick={onRemove}
        className="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950 text-[var(--text-tertiary)] hover:text-red-600 transition-colors shrink-0">
        <Trash2 size={13} />
      </button>
    </div>
  )
}

export default function SegmentsPage() {
  const { toast }           = useToast()
  const [segments, setSegs] = useState([])
  const [tags, setTags]     = useState([])
  const [loading, setLoading] = useState(true)
  const [modal, setModal]   = useState(null)
  const [form, setForm]     = useState({ name: '', rules: [{ field: 'opted_in', operator: 'is', value: '1' }] })
  const [saving, setSaving] = useState(false)
  const [expanded, setExp]  = useState(null)

  const load = () => {
    setLoading(true)
    Promise.all([
      api.get('/segments'),
      api.get('/tags'),
    ]).then(([s, t]) => {
      setSegs(s.data.data ?? [])
      setTags(t.data.data ?? [])
    }).finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [])

  const openCreate = () => {
    setForm({ name: '', rules: [{ field: 'opted_in', operator: 'is', value: '1' }] })
    setModal('create')
  }

  const updateRule = (i, rule) => setForm(p => ({ ...p, rules: p.rules.map((r, idx) => idx === i ? rule : r) }))
  const removeRule = (i) => setForm(p => ({ ...p, rules: p.rules.filter((_, idx) => idx !== i) }))
  const addRule    = () => setForm(p => ({ ...p, rules: [...p.rules, { field: 'opted_in', operator: 'is', value: '1' }] }))

  const save = async () => {
    if (!form.name.trim()) { toast.error('Segment name is required'); return }
    setSaving(true)
    try {
      await api.post('/segments', form)
      toast.success('Segment created')
      setModal(null)
      load()
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  const remove = async (s) => {
    if (!confirm(`Delete segment "${s.name}"? This cannot be undone.`)) return
    try {
      await api.delete(`/segments/${s.id}`)
      toast.success('Segment deleted')
      load()
    } catch {
      toast.error('Delete failed')
    }
  }

  return (
    <div className="space-y-5">
      <PageHeader
        title="Segments"
        subtitle={`${segments.length} segments`}
        action={<Button leftIcon={<Plus size={14} />} onClick={openCreate}>New Segment</Button>}
      />

      {loading ? (
        <div className="space-y-3">
          {[...Array(4)].map((_, i) => <Skeleton key={i} className="h-20 rounded-xl" />)}
        </div>
      ) : !segments.length ? (
        <Card>
          <EmptyState
            icon={Filter}
            title="No segments yet"
            description="Segments let you target contacts based on criteria like tags, opt-in status, or location."
            action={<Button leftIcon={<Plus size={14} />} onClick={openCreate}>Create First Segment</Button>}
          />
        </Card>
      ) : (
        <div className="space-y-3">
          {segments.map((s, i) => (
            <motion.div
              key={s.id}
              initial={{ opacity: 0, y: 6 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.04 }}
              className="rounded-xl bg-[var(--surface)] border border-[var(--border)] shadow-[var(--shadow-sm)] overflow-hidden"
            >
              <div className="flex items-center gap-4 px-5 py-4">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-950 text-brand-600 dark:text-brand-400">
                  <Filter size={16} />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-semibold text-sm text-[var(--text-primary)]">{s.name}</p>
                  <div className="flex items-center gap-3 mt-0.5">
                    <span className="text-xs text-[var(--text-tertiary)] flex items-center gap-1">
                      <Users size={10} />
                      {(s.eligible_count ?? 0).toLocaleString()} contacts
                    </span>
                    <span className="text-xs text-[var(--text-tertiary)]">
                      {s.campaigns_count ?? 0} campaigns
                    </span>
                    {s.rules?.length > 0 && (
                      <span className="text-xs text-[var(--text-tertiary)]">
                        {s.rules.length} rule{s.rules.length !== 1 ? 's' : ''}
                      </span>
                    )}
                  </div>
                </div>
                <div className="flex items-center gap-1 shrink-0">
                  {s.rules?.length > 0 && (
                    <button
                      onClick={() => setExp(expanded === s.id ? null : s.id)}
                      className="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-tertiary)] hover:text-[var(--text-primary)] transition-colors"
                    >
                      {expanded === s.id ? <ChevronUp size={13} /> : <ChevronDown size={13} />}
                    </button>
                  )}
                  <button
                    onClick={() => remove(s)}
                    className="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950 text-[var(--text-tertiary)] hover:text-red-600 transition-colors"
                  >
                    <Trash2 size={13} />
                  </button>
                </div>
              </div>

              {expanded === s.id && s.rules?.length > 0 && (
                <motion.div
                  initial={{ height: 0, opacity: 0 }}
                  animate={{ height: 'auto', opacity: 1 }}
                  exit={{ height: 0, opacity: 0 }}
                  className="border-t border-[var(--border)] px-5 py-3 bg-[var(--surface-2)]"
                >
                  <p className="text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide mb-2">Rules</p>
                  <div className="space-y-1.5">
                    {s.rules.map((rule, ri) => (
                      <div key={ri} className="flex items-center gap-1.5 text-xs text-[var(--text-secondary)]">
                        {ri > 0 && <span className="text-brand-500 font-semibold">AND</span>}
                        <span className="bg-[var(--surface)] border border-[var(--border)] rounded-md px-2 py-0.5 font-mono">
                          {rule.field} {rule.operator} {rule.value}
                        </span>
                      </div>
                    ))}
                  </div>
                </motion.div>
              )}
            </motion.div>
          ))}
        </div>
      )}

      <Modal
        open={!!modal}
        onClose={() => setModal(null)}
        title="New Segment"
        size="lg"
        footer={
          <>
            <Button variant="secondary" onClick={() => setModal(null)}>Cancel</Button>
            <Button loading={saving} onClick={save}>Create Segment</Button>
          </>
        }
      >
        <div className="space-y-5">
          <Input
            label="Segment name"
            required
            value={form.name}
            onChange={e => setForm(p => ({ ...p, name: e.target.value }))}
            placeholder="e.g. Active opted-in patients"
          />

          <div>
            <div className="flex items-center justify-between mb-3">
              <p className="text-sm font-medium text-[var(--text-primary)]">Filter rules</p>
              <Button variant="ghost" size="xs" leftIcon={<Plus size={12} />} onClick={addRule}>Add rule</Button>
            </div>
            <div className="space-y-3 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] p-4">
              {form.rules.map((rule, i) => (
                <RuleRow
                  key={i}
                  rule={rule}
                  index={i}
                  tags={tags}
                  onChange={r => updateRule(i, r)}
                  onRemove={() => removeRule(i)}
                />
              ))}
              {!form.rules.length && (
                <p className="text-sm text-[var(--text-tertiary)] text-center py-2">No rules — all opted-in contacts will match</p>
              )}
            </div>
          </div>
        </div>
      </Modal>
    </div>
  )
}
