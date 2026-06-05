import React, { useEffect, useState } from 'react'
import { motion } from 'framer-motion'
import { Filter, Plus, Trash2, Users, ChevronDown, ChevronUp } from 'lucide-react'
import api from '../services/api'
import { useToast } from '../context/ToastContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input, Select } from '../components/ui/Input'
import { Modal } from '../components/ui/Modal'
import { EmptyState } from '../components/ui/EmptyState'
import { Skeleton } from '../components/ui/Skeleton'

// Fields available for filtering — only fields that exist on the contacts table
const CONDITION_FIELDS = [
  { value: 'opted_in',   label: 'Opt-in status' },
  { value: 'tag',        label: 'Tag' },
  { value: 'country',    label: 'Country (phone prefix)' },
  { value: 'created_at', label: 'Created date' },
]

const OPERATORS = {
  opted_in:   [{ value: 'is', label: 'is' }],
  tag:        [{ value: 'has', label: 'has tag' }, { value: 'not_has', label: 'does not have tag' }],
  country:    [{ value: 'is', label: 'is (e.g. +966)' }, { value: 'is_not', label: 'is not' }],
  created_at: [{ value: 'before', label: 'before' }, { value: 'after', label: 'after' }, { value: 'within_days', label: 'within last N days' }],
}

const EMPTY_CONDITION = { field: 'opted_in', operator: 'is', value: '1' }

function ConditionRow({ condition, index, tags, onChange, onRemove }) {
  const ops = OPERATORS[condition.field] ?? [{ value: 'is', label: 'is' }]

  return (
    <div className="flex items-center gap-2 flex-wrap">
      {index > 0 && (
        <span className="text-xs font-semibold text-brand-600 bg-brand-50 dark:bg-brand-950 dark:text-brand-400 px-2 py-0.5 rounded-full shrink-0">AND</span>
      )}
      <Select
        value={condition.field}
        onChange={e => onChange({ ...EMPTY_CONDITION, field: e.target.value })}
        className="flex-1 min-w-[130px]"
      >
        {CONDITION_FIELDS.map(f => <option key={f.value} value={f.value}>{f.label}</option>)}
      </Select>
      <Select
        value={condition.operator}
        onChange={e => onChange({ ...condition, operator: e.target.value })}
        className="flex-1 min-w-[130px]"
      >
        <option value="">operator</option>
        {ops.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
      </Select>

      {condition.field === 'opted_in' && (
        <Select value={condition.value} onChange={e => onChange({ ...condition, value: e.target.value })} className="flex-1 min-w-[100px]">
          <option value="">select</option>
          <option value="1">Opted in</option>
          <option value="0">Opted out</option>
        </Select>
      )}
      {condition.field === 'tag' && (
        <Select value={condition.value} onChange={e => onChange({ ...condition, value: e.target.value })} className="flex-1 min-w-[130px]">
          <option value="">select tag</option>
          {tags.map(t => <option key={t.id} value={String(t.id)}>{t.name}</option>)}
        </Select>
      )}
      {condition.field === 'country' && (
        <Input
          value={condition.value}
          onChange={e => onChange({ ...condition, value: e.target.value })}
          placeholder="+966"
          className="flex-1 min-w-[100px]"
        />
      )}
      {condition.field === 'created_at' && (
        condition.operator === 'within_days'
          ? <Input type="number" min="1" value={condition.value} onChange={e => onChange({ ...condition, value: e.target.value })} placeholder="days" className="flex-1 min-w-[80px]" />
          : <Input type="date" value={condition.value} onChange={e => onChange({ ...condition, value: e.target.value })} className="flex-1 min-w-[150px]" />
      )}

      <button
        onClick={onRemove}
        className="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950 text-[var(--text-tertiary)] hover:text-red-600 transition-colors shrink-0"
      >
        <Trash2 size={13} />
      </button>
    </div>
  )
}

const FIELD_LABEL = Object.fromEntries(CONDITION_FIELDS.map(f => [f.value, f.label]))

export default function SegmentsPage() {
  const { toast }              = useToast()
  const [segments, setSegs]    = useState([])
  const [tags, setTags]        = useState([])
  const [loading, setLoading]  = useState(true)
  const [modal, setModal]      = useState(null)
  const [form, setForm]        = useState({ name: '', conditions: [{ ...EMPTY_CONDITION }] })
  const [saving, setSaving]    = useState(false)
  const [expanded, setExp]     = useState(null)

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
    setForm({ name: '', conditions: [{ ...EMPTY_CONDITION }] })
    setModal('create')
  }

  const updateCondition = (i, c) => setForm(p => ({ ...p, conditions: p.conditions.map((r, idx) => idx === i ? c : r) }))
  const removeCondition = (i) => setForm(p => ({ ...p, conditions: p.conditions.filter((_, idx) => idx !== i) }))
  const addCondition    = () => setForm(p => ({ ...p, conditions: [...p.conditions, { ...EMPTY_CONDITION }] }))

  const save = async () => {
    if (!form.name.trim()) { toast.error('Segment name is required'); return }
    const invalid = form.conditions.some(c => !c.operator || !c.value)
    if (form.conditions.length > 0 && invalid) { toast.error('Please complete all filter conditions'); return }
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
        subtitle={`${segments.length} segment${segments.length !== 1 ? 's' : ''}`}
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
            description="Segments let you target contacts based on criteria like tags, opt-in status, or phone country."
            action={<Button leftIcon={<Plus size={14} />} onClick={openCreate}>Create First Segment</Button>}
          />
        </Card>
      ) : (
        <div className="space-y-3">
          {segments.map((s, i) => {
            const conds = s.conditions ?? []
            return (
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
                      {conds.length > 0 && (
                        <span className="text-xs text-[var(--text-tertiary)]">
                          {conds.length} condition{conds.length !== 1 ? 's' : ''}
                        </span>
                      )}
                    </div>
                  </div>
                  <div className="flex items-center gap-1 shrink-0">
                    {conds.length > 0 && (
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

                {expanded === s.id && conds.length > 0 && (
                  <motion.div
                    initial={{ height: 0, opacity: 0 }}
                    animate={{ height: 'auto', opacity: 1 }}
                    exit={{ height: 0, opacity: 0 }}
                    className="border-t border-[var(--border)] px-5 py-3 bg-[var(--surface-2)]"
                  >
                    <p className="text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide mb-2">Conditions</p>
                    <div className="space-y-1.5">
                      {conds.map((c, ci) => (
                        <div key={ci} className="flex items-center gap-1.5 text-xs text-[var(--text-secondary)]">
                          {ci > 0 && <span className="text-brand-500 font-semibold">AND</span>}
                          <span className="bg-[var(--surface)] border border-[var(--border)] rounded-md px-2 py-0.5 font-mono">
                            {FIELD_LABEL[c.field] ?? c.field} {c.operator} {c.value}
                          </span>
                        </div>
                      ))}
                    </div>
                  </motion.div>
                )}
              </motion.div>
            )
          })}
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
              <p className="text-sm font-medium text-[var(--text-primary)]">Filter conditions</p>
              <Button variant="ghost" size="xs" leftIcon={<Plus size={12} />} onClick={addCondition}>Add condition</Button>
            </div>
            <div className="space-y-3 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] p-4">
              {form.conditions.map((c, i) => (
                <ConditionRow
                  key={i}
                  condition={c}
                  index={i}
                  tags={tags}
                  onChange={updated => updateCondition(i, updated)}
                  onRemove={() => removeCondition(i)}
                />
              ))}
              {!form.conditions.length && (
                <p className="text-sm text-[var(--text-tertiary)] text-center py-2">No conditions — all opted-in contacts will match</p>
              )}
            </div>
          </div>
        </div>
      </Modal>
    </div>
  )
}
