import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import { useState } from 'react';

export default function Index({ auth, inventory, filters }) {
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        name: '', rate: '', hsn_code: '', unit: 'Unit'
    });

    const [editMode, setEditMode] = useState(false);
    const [originalName, setOriginalName] = useState('');

    const handleSearch = (e) => {
        router.get(route('inventory.index'), { search: e.target.value }, { preserveState: true });
    };

    const submit = (e) => {
        e.preventDefault();
        if (editMode) {
            put(route('inventory.update', originalName), {
                onSuccess: () => { setEditMode(false); reset(); }
            });
        } else {
            post(route('inventory.store'), { onSuccess: () => reset() });
        }
    };

    const startEdit = (item) => {
        clearErrors();
        setEditMode(true);
        setOriginalName(item.name);
        setData(item);
    };

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Inventory Manager</h2>}>
            <Head title="Inventory" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    {/* Form Section */}
                    <div className="p-6 bg-white shadow sm:rounded-lg">
                        <form onSubmit={submit} className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div className="md:col-span-2">
                                <InputLabel value="Product Name" />
                                <TextInput className="w-full" value={data.name} onChange={e => setData('name', e.target.value)} />
                                <InputError message={errors.name} />
                            </div>
                            <div>
                                <InputLabel value="Rate (₹)" />
                                <TextInput type="number" step="0.01" className="w-full" value={data.rate} onChange={e => setData('rate', e.target.value)} />
                                <InputError message={errors.rate} />
                            </div>
                            <div>
                                <InputLabel value="HSN Code" />
                                <TextInput className="w-full" value={data.hsn_code} onChange={e => setData('hsn_code', e.target.value)} />
                                <InputError message={errors.hsn_code} />
                            </div>
                            <div>
                                <InputLabel value="Unit" />
                                <select
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500"
                                    value={data.unit}
                                    onChange={e => setData('unit', e.target.value)}
                                >
                                    <option value="Unit">Unit</option>
                                    <option value="Bag">Bag</option>
                                    <option value="Pouch">Pouch</option>
                                    <option value="Pair">Pair</option>
                                    <option value="Piece">Piece</option>
                                    <option value="kg">kg</option>
                                </select>
                            </div>

                            <div className="md:col-span-4 flex items-center gap-4">
                                <PrimaryButton disabled={processing}>{editMode ? 'Update Item' : 'Add to Inventory'}</PrimaryButton>
                                {editMode && <SecondaryButton onClick={() => {setEditMode(false); reset();}}>Cancel</SecondaryButton>}
                            </div>
                        </form>
                    </div>

                    {/* Table Section */}
                    <div className="bg-white shadow sm:rounded-lg p-6">
                        <div className="mb-6 flex justify-between items-center">
                            <h3 className="text-lg font-medium text-gray-900">Stock Items</h3>
                            <TextInput placeholder="Search inventory..." className="w-1/3" defaultValue={filters.search} onChange={handleSearch} />
                        </div>

                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Item Name</th>
                                <th className="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">HSN</th>
                                <th className="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Rate</th>
                                <th className="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Unit</th>
                                <th className="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                            </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                            {inventory.map((item) => (
                                <tr key={item.name} className="hover:bg-gray-50">
                                    <td className="px-6 py-4 font-medium text-gray-900">{item.name}</td>
                                    <td className="px-6 py-4 text-sm text-gray-600">{item.hsn_code}</td>
                                    <td className="px-6 py-4 text-sm text-gray-900">₹{parseFloat(item.rate).toLocaleString('en-IN')}</td>
                                    <td className="px-6 py-4 text-sm text-gray-600">{item.unit}</td>
                                    <td className="px-6 py-4 text-right text-sm font-medium space-x-3">
                                        <button onClick={() => startEdit(item)} className="text-indigo-600 hover:text-indigo-900">Edit</button>
                                        <button onClick={() => confirm('Delete item?') && router.delete(route('inventory.destroy', item.name))} className="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
