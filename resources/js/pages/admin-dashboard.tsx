import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { BriefcaseBusiness, Gauge, Pencil, Plus, ShieldCheck, Trash2, Truck, Wrench, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Admin Dashboard', href: '/admin/dashboard' }];

interface DashboardStats {
    totalEquipment: number;
    activeRentals: number;
    available: number;
    maintenance: number;
}

interface AdminDashboardProps {
    stats: DashboardStats;
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
    machine_status: 'Available' | 'Rented' | 'Maintenance';
}

interface ContractorOption {
    id: number;
    contractor_name: string;
}

interface ProjectSiteOption {
    id: number;
    project_site: string;
}

interface RentalForm {
    heavy_equipment_id: string;
    contractor_id: string;
    project_site_id: string;
    start_date: string;
    end_date: string;
    fuel_liters_used: string;
    fuel_rate: string;
}

interface EquipmentForm {
    equipment_tag: string;
    machine_model: string;
    daily_rate: string;
    machine_status: 'Available' | 'Maintenance';
}

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
        throw new Error(validationMessages[0] ?? result.message ?? 'The equipment could not be saved.');
    }

    return result;
}

export default function AdminDashboard({ stats, rentals, equipmentOptions, contractors, projectSites }: AdminDashboardProps) {
    const [equipment, setEquipment] = useState<EquipmentForm>({
        equipment_tag: '',
        machine_model: '',
        daily_rate: '',
        machine_status: 'Available',
    });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [message, setMessage] = useState('');
    const [editingRentalId, setEditingRentalId] = useState<number | null>(null);
    const [rentalForm, setRentalForm] = useState<RentalForm>({
        heavy_equipment_id: '',
        contractor_id: '',
        project_site_id: '',
        start_date: '',
        end_date: '',
        fuel_liters_used: '0',
        fuel_rate: '',
    });

    const cards = [
        { label: 'Total Equipment', value: stats.totalEquipment, Icon: Truck },
        { label: 'Active Rentals', value: stats.activeRentals, Icon: BriefcaseBusiness },
        { label: 'Available', value: stats.available, Icon: Gauge },
        { label: 'Maintenance', value: stats.maintenance, Icon: Wrench },
    ];

    const submitEquipment = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setBusy(true);
        setError('');
        setMessage('');

        try {
            await sendJson('/heavy-equipment', 'POST', {
                ...equipment,
                daily_rate: Number(equipment.daily_rate),
            });
            setEquipment({ equipment_tag: '', machine_model: '', daily_rate: '', machine_status: 'Available' });
            setMessage('Equipment added successfully.');
            router.reload({ only: ['stats'] });
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : 'The equipment could not be saved.');
        } finally {
            setBusy(false);
        }
    };

    const editRental = (rental: Rental) => {
        setEditingRentalId(rental.id);
        setRentalForm({
            heavy_equipment_id: String(rental.heavy_equipment_id),
            contractor_id: String(rental.contractor_id),
            project_site_id: String(rental.project_site_id),
            start_date: rental.start_date.slice(0, 10),
            end_date: rental.end_date.slice(0, 10),
            fuel_liters_used: String(rental.fuel_liters_used),
            fuel_rate: String(rental.fuel_rate),
        });
        setError('');
        setMessage('');
        document.getElementById('admin-rental-form')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    const submitRental = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (editingRentalId === null) return;
        setBusy(true);
        setError('');
        setMessage('');

        try {
            await sendJson(`/equipment-rentals/${editingRentalId}`, 'PUT', {
                ...rentalForm,
                fuel_liters_used: Number(rentalForm.fuel_liters_used),
                fuel_rate: Number(rentalForm.fuel_rate),
            });
            setEditingRentalId(null);
            setMessage('Rental updated successfully.');
            router.reload({ only: ['stats', 'rentals'] });
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : 'The rental could not be updated.');
        } finally {
            setBusy(false);
        }
    };

    const deleteRental = async (rental: Rental) => {
        if (!window.confirm(`Delete the rental for ${rental.contractor.contractor_name}?`)) return;
        setBusy(true);
        setError('');
        setMessage('');

        try {
            await sendJson(`/equipment-rentals/${rental.id}`, 'DELETE');
            setMessage('Rental deleted successfully.');
            if (editingRentalId === rental.id) setEditingRentalId(null);
            router.reload({ only: ['stats', 'rentals'] });
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : 'The rental could not be deleted.');
        } finally {
            setBusy(false);
        }
    };

    const updateRentalForm = (field: keyof RentalForm, value: string) => {
        setRentalForm((current) => ({ ...current, [field]: value }));
    };

    const fieldClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-200';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />
            <main className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <section className="rounded-2xl bg-gradient-to-r from-slate-900 via-sky-900 to-blue-900 p-6 text-white shadow-lg">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div className="mb-2 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.2em] text-blue-100">
                                <ShieldCheck className="h-4 w-4" />
                                Admin 
                            </div>
                            <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">Admin dashboard</h1>
                            <p className="mt-2 max-w-xl text-sm text-blue-100">
                                Monitor equipment, rentals, and maintenance status from a central admin view.
                            </p>
                        </div>

                        <a href="#add-equipment" className="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-900 transition hover:bg-slate-100">
                            <Plus className="h-4 w-4" />
                            Add equipment
                        </a>
                    </div>S
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {cards.map(({ label, value, Icon }) => (
                        <article key={label} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
                                <Icon className="h-5 w-5" />
                            </div>
                            <p className="text-3xl font-bold text-slate-900">{value}</p>
                            <p className="mt-2 text-sm text-slate-600">{label}</p>
                        </article>
                    ))}
                </section>

                <section id="add-equipment" className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div className="mb-5">
                        <h2 className="text-lg font-semibold text-slate-900">Add new equipment</h2>
                        <p className="text-sm text-slate-500">Register a machine in the equipment inventory.</p>
                    </div>
                    <form onSubmit={submitEquipment} className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <label className="text-sm font-medium text-slate-700">
                            Equipment tag
                            <input className={fieldClass} value={equipment.equipment_tag} onChange={(event) => setEquipment({ ...equipment, equipment_tag: event.target.value })} required maxLength={50} />
                        </label>
                        <label className="text-sm font-medium text-slate-700">
                            Machine model
                            <input className={fieldClass} value={equipment.machine_model} onChange={(event) => setEquipment({ ...equipment, machine_model: event.target.value })} required maxLength={150} />
                        </label>
                        <label className="text-sm font-medium text-slate-700">
                            Daily rate
                            <input className={fieldClass} type="number" min="0" step="0.01" value={equipment.daily_rate} onChange={(event) => setEquipment({ ...equipment, daily_rate: event.target.value })} required />
                        </label>
                        <label className="text-sm font-medium text-slate-700">
                            Initial status
                            <select className={fieldClass} value={equipment.machine_status} onChange={(event) => setEquipment({ ...equipment, machine_status: event.target.value as EquipmentForm['machine_status'] })}>
                                <option value="Available">Available</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </label>
                        <div className="sm:col-span-2 lg:col-span-4">
                            <button type="submit" disabled={busy} className="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60">
                                <Plus className="h-4 w-4" />
                                {busy ? 'Adding…' : 'Add equipment'}
                            </button>
                            {error && <p role="alert" className="mt-3 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>}
                            {message && <p role="status" className="mt-3 rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">{message}</p>}
                        </div>
                    </form>
                </section>

                {editingRentalId !== null && (
                    <section id="admin-rental-form" className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div className="mb-5 flex items-center justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-semibold text-slate-900">Edit rental</h2>
                                <p className="text-sm text-slate-500">Update the transaction details.</p>
                            </div>
                            <button type="button" onClick={() => setEditingRentalId(null)} className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-slate-600 hover:bg-slate-100">
                                <X className="size-4" /> Cancel
                            </button>
                        </div>
                        <form onSubmit={submitRental} className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <label className="text-sm font-medium text-slate-700">
                                Equipment
                                <select className={fieldClass} value={rentalForm.heavy_equipment_id} onChange={(event) => updateRentalForm('heavy_equipment_id', event.target.value)} required>
                                    {equipmentOptions.map((option) => <option key={option.id} value={option.id}>{option.equipment_tag} — {option.machine_model}</option>)}
                                </select>
                            </label>
                            <label className="text-sm font-medium text-slate-700">
                                Renter
                                <select className={fieldClass} value={rentalForm.contractor_id} onChange={(event) => updateRentalForm('contractor_id', event.target.value)} required>
                                    {contractors.map((contractor) => <option key={contractor.id} value={contractor.id}>{contractor.contractor_name}</option>)}
                                </select>
                            </label>
                            <label className="text-sm font-medium text-slate-700">
                                Project site
                                <select className={fieldClass} value={rentalForm.project_site_id} onChange={(event) => updateRentalForm('project_site_id', event.target.value)} required>
                                    {projectSites.map((site) => <option key={site.id} value={site.id}>{site.project_site}</option>)}
                                </select>
                            </label>
                            <label className="text-sm font-medium text-slate-700">
                                Start date
                                <input className={fieldClass} type="date" value={rentalForm.start_date} onChange={(event) => updateRentalForm('start_date', event.target.value)} required />
                            </label>
                            <label className="text-sm font-medium text-slate-700">
                                End date
                                <input className={fieldClass} type="date" min={rentalForm.start_date} value={rentalForm.end_date} onChange={(event) => updateRentalForm('end_date', event.target.value)} required />
                            </label>
                            <label className="text-sm font-medium text-slate-700">
                                Fuel used (liters)
                                <input className={fieldClass} type="number" min="0" step="0.01" value={rentalForm.fuel_liters_used} onChange={(event) => updateRentalForm('fuel_liters_used', event.target.value)} />
                            </label>
                            <label className="text-sm font-medium text-slate-700">
                                Fuel rate per liter
                                <input className={fieldClass} type="number" min="0" step="0.01" value={rentalForm.fuel_rate} onChange={(event) => updateRentalForm('fuel_rate', event.target.value)} required />
                            </label>
                            <div className="flex items-center">
                                <button type="submit" disabled={busy} className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-60">
                                    {busy ? 'Saving…' : 'Save changes'}
                                </button>
                            </div>
                        </form>
                    </section>
                )}

                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-200 px-5 py-4 sm:px-6">
                        <h2 className="text-lg font-semibold text-slate-900">Recent rentals</h2>
                        <p className="text-sm text-slate-500">Edit or remove a rental record at any time. Showing {rentals.length} records.</p>
                    </div>
                    {rentals.length ? (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[800px] text-left text-sm">
                                <thead className="bg-slate-50 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                    <tr>
                                        <th className="px-5 py-3">Renter</th>
                                        <th className="px-4 py-3">Equipment</th>
                                        <th className="px-4 py-3">Project site</th>
                                        <th className="px-4 py-3">Rental dates</th>
                                        <th className="px-4 py-3">Total</th>
                                        <th className="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rentals.map((rental) => (
                                        <tr key={rental.id} className="text-slate-700 hover:bg-slate-50/70">
                                            <td className="px-5 py-3 font-medium text-slate-900">{rental.contractor.contractor_name}</td>
                                            <td className="px-4 py-3"><span className="font-medium">{rental.heavy_equipment.equipment_tag}</span><span className="block text-xs text-slate-500">{rental.heavy_equipment.machine_model}</span></td>
                                            <td className="px-4 py-3">{rental.project_site.project_site}</td>
                                            <td className="px-4 py-3 whitespace-nowrap">{rental.start_date.slice(0, 10)} – {rental.end_date.slice(0, 10)}</td>
                                            <td className="px-4 py-3 font-medium">${Number(rental.total_rental_cost).toFixed(2)}</td>
                                            <td className="px-5 py-3 text-right">
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
                        <div className="px-6 py-10 text-center text-sm text-slate-500">There are no rental transactions yet.</div>
                    )}
                </section>
            </main>
        </AppLayout>
    );
}
