import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import { useState } from 'react';

export default function Index({ auth, clients, filters }) {
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        name: '', email: '', address: '', gst_number: '', state: '', state_code: ''
    });

    const [editMode, setEditMode] = useState(false);

    const handleSearch = (e) => {
        router.get(route('clients.index'), { search: e.target.value }, { preserveState: true });
    };

    const submit = (e) => {
        e.preventDefault();
        if (editMode) {
            put(route('clients.update', data.email), {
                onSuccess: () => { setEditMode(false); reset(); }
            });
        } else {
            post(route('clients.store'), { onSuccess: () => reset() });
        }
    };

    const startEdit = (client) => {
        clearErrors();
        setEditMode(true);
        setData(client);
    };

    const cancelEdit = () => {
        setEditMode(false);
        reset();
    };

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Client Database</h2>}>
            <Head title="Clients" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    {/* Form Section */}
                    <div className="p-6 bg-white shadow sm:rounded-lg">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">{editMode ? 'Edit Client' : 'Add New Client'}</h3>
                        <form onSubmit={submit} className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <InputLabel value="Name" />
                                <TextInput className="w-full" value={data.name} onChange={e => setData('name', e.target.value)} />
                                <InputError message={errors.name} />
                            </div>
                            <div>
                                <InputLabel value="Email" />
                                <TextInput className="w-full" value={data.email} disabled={editMode} onChange={e => setData('email', e.target.value)} />
                                <InputError message={errors.email} />
                            </div>
                            <div>
                                <InputLabel value="GST Number" />
                                <TextInput className="w-full" value={data.gst_number} onChange={e => setData('gst_number', e.target.value)} />
                                <InputError message={errors.gst_number} />
                            </div>
                            <div className="md:col-span-2">
                                <InputLabel value="Address" />
                                <TextInput className="w-full" value={data.address} onChange={e => setData('address', e.target.value)} />
                                <InputError message={errors.address} />
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div>
                                    <InputLabel value="State" />
                                    <TextInput className="w-full" value={data.state} onChange={e => setData('state', e.target.value)} />
                                    <InputError message={errors.state} />
                                </div>
                                <div>
                                    <InputLabel value="Code" />
                                    <TextInput className="w-full" value={data.state_code} onChange={e => setData('state_code', e.target.value)} />
                                    <InputError message={errors.state_code} />
                                </div>
                            </div>

                            <div className="md:col-span-3 flex items-center gap-4">
                                <PrimaryButton disabled={processing}>{editMode ? 'Save Changes' : 'Create Client'}</PrimaryButton>
                                {editMode && <SecondaryButton onClick={cancelEdit}>Cancel</SecondaryButton>}
                            </div>
                        </form>
                    </div>

                    {/* Table Section */}
                    <div className="bg-white overflow-hidden shadow sm:rounded-lg p-6">
                        <div className="mb-6 flex justify-between items-center">
                            <h3 className="text-lg font-medium text-gray-900">Client Directory</h3>
                            <TextInput
                                placeholder="Search by name, email, or GST..."
                                className="w-1/3"
                                defaultValue={filters.search}
                                onChange={handleSearch}
                            />
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 border">
                                <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Name</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Contact/GST</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Location</th>
                                    <th className="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                                </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                {clients.length > 0 ? clients.map((client) => (
                                    <tr key={client.email} className="hover:bg-gray-50 transition">
                                        <td className="px-4 py-4 whitespace-nowrap font-medium text-gray-900">{client.name}</td>
                                        <td className="px-4 py-4">
                                            <div className="text-sm text-gray-900">{client.email}</div>
                                            <div className="text-xs text-gray-500 font-mono">GST: {client.gst_number}</div>
                                        </td>
                                        <td className="px-4 py-4 text-sm text-gray-600">
                                            {client.address}<br/>
                                            <span className="font-semibold">{client.state}</span> ({client.state_code})
                                        </td>
                                        <td className="px-4 py-4 text-right text-sm font-medium space-x-3">
                                            <button onClick={() => startEdit(client)} className="text-indigo-600 hover:text-indigo-900">Edit</button>
                                            <button
                                                onClick={() => confirm('Delete client?') && router.delete(route('clients.destroy', client.email))}
                                                className="text-red-600 hover:text-red-900"
                                            >Delete</button>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="4" className="px-4 py-8 text-center text-gray-500">No clients found.</td></tr>
                                )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
