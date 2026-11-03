import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { CalendarDays, CircleCheck, ClipboardList, Construction, Pencil, Plus, Trash2, Truck, Wrench, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface DashboardProps {
    stats: {
        totalEquipment: number;
        activeRentals: number;
        available: number;
        maintenance: number;
    };
    rentals: Rental[];
    equipmentOptions: EquipmentOption[];
    contractors: ContractorOption[];
    projectSites: ProjectSiteOption[];
}

interface Rental {
    id: number;
    heavy_equipment_id: number;
    contractor_id: number;
    project_site_id: number;
    start_date: string;
    end_date: string;
    fuel_liters_used: number | string;
    fuel_rate: number | string;
    total_rental_cost: number | string;
    heavy_equipment: { equipment_tag: string; machine_model: string };
    contractor: { contractor_name: string };
    project_site: { project_site: string };
}

interface EquipmentOption {
    id: number;
    equipment_tag: string;
    machine_model: string;
    daily_rate: number | string;
}

interface ContractorOption {
    id: number;
    contractor_name: string;
}

interface ProjectSiteOption {
    id: number;
    project_site: string;
}

interface RentalFormState {
    heavy_equipment_id: string;
    contractor_id: string;
    project_site_id: string;
    start_date: string;
    end_date: string;
    fuel_liters_used: string;
    fuel_rate: string;
}

const emptyRentalForm = (): RentalFormState => ({
    heavy_equipment_id: '',
    contractor_id: '',
    project_site_id: '',
    start_date: new Date().toISOString().slice(0, 10),
    end_date: new Date().toISOString().slice(0, 10),
    fuel_liters_used: '0',
    fuel_rate: '',
});

async function sendJson(url: string, method: string, data?: unknown) {
    const xsrfCookie = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='));
    const xsrfToken = xsrfCookie ? decodeURIComponent(xsrfCookie.slice('XSRF-TOKEN='.length)) : '';
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': xsrfToken,
        },
        body: data === undefined ? undefined : JSON.stringify(data),
    });
    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
        const validationMessages = Object.values(result.errors ?? {}).flat() as string[];
        throw new Error(validationMessages[0] ?? result.message ?? 'The request could not be completed.');
    }

    return result;
}

const monthLabel = new Intl.DateTimeFormat('en', {
    month: 'long',
    year: 'numeric',
}).format(new Date()).toUpperCase();

export default function Dashboard({ stats, rentals, equipmentOptions, contractors, projectSites }: DashboardProps) {
    const [form, setForm] = useState<RentalFormState>(emptyRentalForm);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [contractorName, setContractorName] = useState('');
    const [siteName, setSiteName] = useState('');
    const [busy, setBusy] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');

    const cards = [
        { label: 'Total Equipment', value: stats.totalEquipment, Icon: Truck, color: 'text-sky-300' },
        { label: 'Active Rentals', value: stats.activeRentals, Icon: ClipboardList, color: 'text-emerald-300' },
        { label: 'Available', value: stats.available, Icon: CircleCheck, color: 'text-green-400' },
        { label: 'Maintenance', value: stats.maintenance, Icon: Wrench, color: 'text-amber-400' },
    ];

    const refreshDashboard = (only: string[] = ['stats', 'rentals', 'equipmentOptions']) => {
        router.reload({ only });
    };

    const updateForm = (field: keyof RentalFormState, value: string) => {
        setForm((current) => ({ ...current, [field]: value }));
    };

    const resetForm = () => {
        setForm(emptyRentalForm());
        setEditingId(null);
        setError('');
    };

    const submitRental = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setBusy(true);
        setError('');
        setMessage('');

        try {
            await sendJson(editingId ? `/equipment-rentals/${editingId}` : '/equipment-rentals', editingId ? 'PUT' : 'POST', {
                ...form,
                fuel_liters_used: Number(form.fuel_liters_used),
                fuel_rate: Number(form.fuel_rate),
            });
            setMessage(editingId ? 'Rental updated successfully.' : 'Rental added successfully.');
            resetForm();
            refreshDashboard();
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : 'The rental could not be saved.');
        } finally {
            setBusy(false);
        }
    };

    const editRental = (rental: Rental) => {
        setEditingId(rental.id);
        setError('');
        setMessage('');
        setForm({
            heavy_equipment_id: String(rental.heavy_equipment_id),
            contractor_id: String(rental.contractor_id),
            project_site_id: String(rental.project_site_id),
            start_date: rental.start_date.slice(0, 10),
            end_date: rental.end_date.slice(0, 10),
            fuel_liters_used: String(rental.fuel_liters_used),
            fuel_rate: String(rental.fuel_rate),
        });
        document.getElementById('rental-form')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    const deleteRental = async (rental: Rental) => {
        if (!window.confirm(`Delete the rental for ${rental.contractor.contractor_name}?`)) return;
        setBusy(true);
        setError('');
        setMessage('');
        try {
            await sendJson(`/equipment-rentals/${rental.id}`, 'DELETE');
            setMessage('Rental deleted successfully.');
            if (editingId === rental.id) resetForm();
            refreshDashboard();
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : 'The rental could not be deleted.');
        } finally {
            setBusy(false);
        }
    };

    const addContractor = async () => {
        if (!contractorName.trim()) return;
        setBusy(true);
        setError('');
        try {
            const contractor = await sendJson('/contractors', 'POST', { contractor_name: contractorName.trim() });
            setForm((current) => ({ ...current, contractor_id: String(contractor.id) }));
            setContractorName('');
            refreshDashboard(['contractors']);
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : 'The contractor could not be added.');
        } finally {
            setBusy(false);
        }
    };

    const addProjectSite = async () => {
        if (!siteName.trim()) return;
        setBusy(true);
        setError('');
        try {
            const site = await sendJson('/project-sites', 'POST', { project_site: siteName.trim() });
            setForm((current) => ({ ...current, project_site_id: String(site.id) }));
            setSiteName('');
            refreshDashboard(['projectSites']);
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : 'The project site could not be added.');
        } finally {
            setBusy(false);
        }
    };

    const fieldClass = 'mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <main className="flex flex-1 flex-col p-4 sm:p-6">
                <section className="w-full rounded-xl bg-[#192858] p-3 text-white shadow-sm sm:p-5" aria-label="Equipment rental overview">
                    <header className="flex items-start justify-between border-b border-white/20 pb-3">
                        <div>
                            <h1 className="text-xl font-bold tracking-tight sm:text-2xl">Equipment Rental</h1>
                            <p className="mt-0.5 text-xs text-blue-100 sm:text-sm">Management Dashboard</p>
                        </div>
                        <div className="flex items-center gap-2 pt-1 text-blue-100" aria-hidden="true">
                            <Construction className="hidden size-5 sm:block" />
                            <CalendarDays className="size-5" />
                        </div>
                    </header>

                    <div className="pb-2 pt-4 text-[11px] font-medium text-blue-100 sm:text-xs">
                        OVERVIEW · {monthLabel}
                    </div>

                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        {cards.map(({ label, value, Icon, color }) => (
                            <article key={label} className="flex min-h-[122px] flex-col justify-between rounded-xl bg-[#203f63] p-3 sm:min-h-[122px] sm:p-4">
                                <Icon className={`size-4 ${color}`} strokeWidth={2} aria-hidden="true" />
                                <div>
                                    <p className="text-3xl leading-none font-bold tracking-tight sm:text-4xl">{value}</p>
                                    <p className="mt-3 text-xs text-blue-100 sm:text-sm">{label}</p>
                                </div>
                            </article>
                        ))}
                    </div>
                </section>

                <section id="rental-form" className="mt-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    <div className="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900">{editingId ? 'Edit rental' : 'Add a rental'}</h2>
                            <p className="text-sm text-slate-500">Record who is renting equipment and where it will be used.</p>
                        </div>
                        {editingId ? (
                            <button type="button" onClick={resetForm} className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-slate-600 hover:bg-slate-100">
                                <X className="size-4" /> Cancel
                            </button>
                        ) : <Plus className="size-5 text-slate-400" aria-hidden="true" />}
                    </div>

                    <form onSubmit={submitRental} className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <label className="text-sm font-medium text-slate-700 sm:col-span-2">
                            Equipment
                            <select className={fieldClass} value={form.heavy_equipment_id} onChange={(event) => updateForm('heavy_equipment_id', event.target.value)} required>
                                <option value="">Select equipment</option>
                                {equipmentOptions.map((equipment) => (
                                    <option key={equipment.id} value={equipment.id}>{equipment.equipment_tag} — {equipment.machine_model} (${Number(equipment.daily_rate).toFixed(2)}/day)</option>
                                ))}
                            </select>
                        </label>
                        <label className="text-sm font-medium text-slate-700 sm:col-span-2">
                            Contractor / renter
                            <select className={fieldClass} value={form.contractor_id} onChange={(event) => updateForm('contractor_id', event.target.value)} required>
                                <option value="">Select renter</option>
                                {contractors.map((contractor) => <option key={contractor.id} value={contractor.id}>{contractor.contractor_name}</option>)}
                            </select>
                            <span className="mt-2 flex gap-2">
                                <input className={fieldClass.replace('mt-1', 'mt-0')} value={contractorName} onChange={(event) => setContractorName(event.target.value)} placeholder="Add a new renter" aria-label="New renter name" />
                                <button type="button" onClick={addContractor} disabled={busy || !contractorName.trim()} className="shrink-0 rounded-md bg-slate-100 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-200 disabled:opacity-50">Add renter</button>
                            </span>
                        </label>
                        <label className="text-sm font-medium text-slate-700 sm:col-span-2">
                            Project site
                            <select className={fieldClass} value={form.project_site_id} onChange={(event) => updateForm('project_site_id', event.target.value)} required>
                                <option value="">Select project site</option>
                                {projectSites.map((site) => <option key={site.id} value={site.id}>{site.project_site}</option>)}
                            </select>
                            <span className="mt-2 flex gap-2">
                                <input className={fieldClass.replace('mt-1', 'mt-0')} value={siteName} onChange={(event) => setSiteName(event.target.value)} placeholder="Add a new project site" aria-label="New project site name" />
                                <button type="button" onClick={addProjectSite} disabled={busy || !siteName.trim()} className="shrink-0 rounded-md bg-slate-100 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-200 disabled:opacity-50">Add site</button>
                            </span>
                        </label>
                        <label className="text-sm font-medium text-slate-700">
                            Start date
                            <input className={fieldClass} type="date" value={form.start_date} onChange={(event) => updateForm('start_date', event.target.value)} required />
                        </label>
                        <label className="text-sm font-medium text-slate-700">
                            End date
                            <input className={fieldClass} type="date" min={form.start_date} value={form.end_date} onChange={(event) => updateForm('end_date', event.target.value)} required />
                        </label>
                        <label className="text-sm font-medium text-slate-700">
                            Fuel used (liters)
                            <input className={fieldClass} type="number" min="0" step="0.01" value={form.fuel_liters_used} onChange={(event) => updateForm('fuel_liters_used', event.target.value)} />
                        </label>
                        <label className="text-sm font-medium text-slate-700">
                            Fuel rate per liter
                            <input className={fieldClass} type="number" min="0" step="0.01" value={form.fuel_rate} onChange={(event) => updateForm('fuel_rate', event.target.value)} required />
                        </label>
                        <div className="flex items-center gap-3 sm:col-span-2 lg:col-span-4">
                            <button type="submit" disabled={busy || !equipmentOptions.length} className="rounded-md bg-[#192858] px-4 py-2 text-sm font-semibold text-white hover:bg-[#24396f] disabled:cursor-not-allowed disabled:opacity-60">
                                {busy ? 'Saving…' : editingId ? 'Save changes' : 'Create rental'}
                            </button>
                            {!equipmentOptions.length && <span className="text-sm text-amber-700">Add equipment before creating a rental.</span>}
                        </div>
                    </form>
                    {error && <p role="alert" className="mt-3 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>}
                    {message && <p role="status" className="mt-3 rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">{message}</p>}
                </section>

                <section className="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-200 px-4 py-4 sm:px-6">
                        <h2 className="text-lg font-semibold text-slate-900">Recent rentals</h2>
                        <p className="text-sm text-slate-500">Edit or remove a rental record at any time.</p>
                    </div>
                    {rentals.length ? (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[760px] text-left text-sm">
                                <thead className="bg-slate-50 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                    <tr>
                                        <th className="px-4 py-3 sm:px-6">Renter</th>
                                        <th className="px-4 py-3">Equipment</th>
                                        <th className="px-4 py-3">Project site</th>
                                        <th className="px-4 py-3">Rental dates</th>
                                        <th className="px-4 py-3">Total</th>
                                        <th className="px-4 py-3 text-right sm:px-6">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rentals.map((rental) => (
                                        <tr key={rental.id} className="text-slate-700 hover:bg-slate-50/70">
                                            <td className="px-4 py-3 font-medium text-slate-900 sm:px-6">{rental.contractor.contractor_name}</td>
                                            <td className="px-4 py-3"><span className="font-medium">{rental.heavy_equipment.equipment_tag}</span><span className="block text-xs text-slate-500">{rental.heavy_equipment.machine_model}</span></td>
                                            <td className="px-4 py-3">{rental.project_site.project_site}</td>
                                            <td className="px-4 py-3 whitespace-nowrap">{rental.start_date.slice(0, 10)} – {rental.end_date.slice(0, 10)}</td>
                                            <td className="px-4 py-3 font-medium">${Number(rental.total_rental_cost).toFixed(2)}</td>
                                            <td className="px-4 py-3 text-right sm:px-6">
                                                <div className="inline-flex gap-1">
                                                    <button type="button" onClick={() => editRental(rental)} aria-label={`Edit rental for ${rental.contractor.contractor_name}`} className="rounded-md p-2 text-slate-600 hover:bg-blue-50 hover:text-blue-700"><Pencil className="size-4" /></button>
                                                    <button type="button" onClick={() => deleteRental(rental)} disabled={busy} aria-label={`Delete rental for ${rental.contractor.contractor_name}`} className="rounded-md p-2 text-slate-600 hover:bg-red-50 hover:text-red-700 disabled:opacity-50"><Trash2 className="size-4" /></button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="px-6 py-10 text-center">
                            <ClipboardList className="mx-auto size-8 text-slate-300" />
                            <p className="mt-2 font-medium text-slate-700">No rentals yet</p>
                            <p className="mt-1 text-sm text-slate-500">Use the form above to add your first equipment rental.</p>
                        </div>
                    )}
                </section>
            </main>
        </AppLayout>
    );
}
