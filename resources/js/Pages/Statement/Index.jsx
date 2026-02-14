import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState, useMemo } from 'react';

// Utility for formatting currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(amount);
};

export default function StatementIndex({
                                           auth,
                                           errors,
                                           transactions,
                                           filename,
                                           total_deposits,
                                           total_withdrawals,
                                           calculated_deposits,
                                           calculated_withdrawals
                                       }) {
    const { data, setData, post, processing, reset } = useForm({
        statement: null,
    });

    const [preview, setPreview] = useState(null);
    const [isDragging, setIsDragging] = useState(false);

    // Calculate totals dynamically - updated for "received" and "paid"
    const summary = useMemo(() => {
        if (!transactions) return { received: 0, paid: 0, net: 0 };

        const received = transactions
            .filter(t => t.type === 'received')
            .reduce((acc, curr) => acc + curr.amount, 0);

        const paid = transactions
            .filter(t => t.type === 'paid')
            .reduce((acc, curr) => acc + curr.amount, 0);

        return { received, paid, net: received - paid };
    }, [transactions]);

    // Check if totals match (within 1 rupee tolerance)
    const depositsMatch = total_deposits && Math.abs(total_deposits - calculated_deposits) < 1;
    const withdrawalsMatch = total_withdrawals && Math.abs(total_withdrawals - calculated_withdrawals) < 1;
    const hasDiscrepancy = total_deposits && (!depositsMatch || !withdrawalsMatch);

    const handleFileChange = (file) => {
        if (!file) return;
        setData('statement', file);

        if (file.type.startsWith('image/')) {
            setPreview(URL.createObjectURL(file));
        } else {
            setPreview(null);
        }
    };

    const handleDrop = (e) => {
        e.preventDefault();
        setIsDragging(false);
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            handleFileChange(e.dataTransfer.files[0]);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('bank-statement.analyze'), {
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        reset();
        setPreview(null);
        window.location.href = route('bank-statement.index');
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Statement Analyzer" />

            <div className="py-12 bg-gray-50 min-h-screen">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                    {/* Header Section */}
                    <div className="flex justify-between items-center">
                        <div>
                            <h2 className="text-3xl font-extrabold text-gray-900 tracking-tight">
                                Financial Insights
                            </h2>
                            <p className="text-gray-500 mt-1">
                                AI-powered extraction for your bank statements.
                            </p>
                        </div>
                        {transactions && (
                            <button
                                onClick={handleReset}
                                className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Analyze New File
                            </button>
                        )}
                    </div>

                    {!transactions ? (
                        /* --- UPLOAD VIEW --- */
                        <div className="max-w-2xl mx-auto">
                            <form onSubmit={handleSubmit} className="bg-white rounded-2xl shadow-xl overflow-hidden">
                                <div className="p-8">
                                    <div
                                        className={`mt-2 flex justify-center px-6 pt-10 pb-12 border-2 border-dashed rounded-xl transition-all duration-200 ${
                                            isDragging
                                                ? 'border-indigo-500 bg-indigo-50'
                                                : 'border-gray-300 hover:border-indigo-400 bg-gray-50'
                                        }`}
                                        onDragOver={(e) => { e.preventDefault(); setIsDragging(true); }}
                                        onDragLeave={() => setIsDragging(false)}
                                        onDrop={handleDrop}
                                    >
                                        <div className="space-y-4 text-center">
                                            {preview ? (
                                                <div className="relative inline-block">
                                                    <img src={preview} alt="Preview" className="h-48 rounded-lg shadow-md mx-auto" />
                                                    <button type="button" onClick={() => {setData('statement', null); setPreview(null)}} className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                    </button>
                                                </div>
                                            ) : (
                                                <div className="mx-auto h-20 w-20 text-indigo-100 bg-indigo-600 rounded-full flex items-center justify-center shadow-lg">
                                                    <svg className="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                            )}

                                            <div className="text-sm text-gray-600">
                                                <label htmlFor="file-upload" className="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                                    <span>Upload a file</span>
                                                    <input
                                                        id="file-upload"
                                                        name="file-upload"
                                                        type="file"
                                                        className="sr-only"
                                                        accept=".pdf,.jpg,.jpeg,.png"
                                                        onChange={(e) => handleFileChange(e.target.files[0])}
                                                    />
                                                </label>
                                                <span className="pl-1">or drag and drop</span>
                                            </div>
                                            <p className="text-xs text-gray-500">PDF, JPG, PNG up to 10MB</p>

                                            {data.statement && !preview && (
                                                <div className="flex items-center justify-center text-sm text-gray-700 bg-gray-100 py-2 px-4 rounded-full inline-block">
                                                    <svg className="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    {data.statement.name}
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {errors.statement && <p className="mt-2 text-sm text-red-600 text-center">{errors.statement}</p>}
                                    {errors.error && <p className="mt-2 text-sm text-red-600 text-center">{errors.error}</p>}
                                </div>
                                <div className="bg-gray-50 px-8 py-4 flex justify-end">
                                    <button
                                        type="submit"
                                        disabled={processing || !data.statement}
                                        className="w-full inline-flex justify-center items-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                    >
                                        {processing ? (
                                            <>
                                                <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Extracting Data...
                                            </>
                                        ) : 'Analyze Statement'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    ) : (
                        /* --- RESULT DASHBOARD VIEW --- */
                        <div className="space-y-6 animate-fade-in-up">

                            {/* Accuracy Warning */}
                            {hasDiscrepancy && (
                                <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                                    <div className="flex">
                                        <div className="flex-shrink-0">
                                            <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                            </svg>
                                        </div>
                                        <div className="ml-3">
                                            <h3 className="text-sm font-medium text-yellow-800">
                                                Transaction Extraction Notice
                                            </h3>
                                            <div className="mt-2 text-sm text-yellow-700">
                                                <p>The AI extracted totals from the statement footer are displayed below. Individual transaction extraction may have minor discrepancies.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Summary Cards */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div className={`bg-white overflow-hidden shadow-sm rounded-xl p-6 border-l-4 ${depositsMatch ? 'border-emerald-500' : 'border-yellow-500'}`}>
                                    <div className="text-sm font-medium text-gray-500 mb-1">
                                        Money Received {depositsMatch && '✓'}
                                    </div>
                                    <div className="text-3xl font-bold text-emerald-600">
                                        {formatCurrency(total_deposits || summary.received)}
                                    </div>
                                    {!depositsMatch && total_deposits && (
                                        <div className="mt-2 pt-2 border-t border-gray-100">
                                            <p className="text-xs text-gray-500">From statement footer</p>
                                            <p className="text-xs text-gray-600 mt-1">
                                                Calculated: {formatCurrency(calculated_deposits)}
                                            </p>
                                        </div>
                                    )}
                                    <p className="text-xs text-gray-500 mt-2">Total deposits/credits</p>
                                </div>

                                <div className={`bg-white overflow-hidden shadow-sm rounded-xl p-6 border-l-4 ${withdrawalsMatch ? 'border-rose-500' : 'border-yellow-500'}`}>
                                    <div className="text-sm font-medium text-gray-500 mb-1">
                                        Money Paid {withdrawalsMatch && '✓'}
                                    </div>
                                    <div className="text-3xl font-bold text-rose-600">
                                        {formatCurrency(total_withdrawals || summary.paid)}
                                    </div>
                                    {!withdrawalsMatch && total_withdrawals && (
                                        <div className="mt-2 pt-2 border-t border-gray-100">
                                            <p className="text-xs text-gray-500">From statement footer</p>
                                            <p className="text-xs text-gray-600 mt-1">
                                                Calculated: {formatCurrency(calculated_withdrawals)}
                                            </p>
                                        </div>
                                    )}
                                    <p className="text-xs text-gray-500 mt-2">Total withdrawals/debits</p>
                                </div>

                                <div className="bg-white overflow-hidden shadow-sm rounded-xl p-6 border-l-4 border-indigo-500">
                                    <div className="text-sm font-medium text-gray-500 mb-1">Net Flow</div>
                                    <div className={`text-3xl font-bold ${
                                        ((total_deposits || summary.received) - (total_withdrawals || summary.paid)) >= 0
                                            ? 'text-emerald-600'
                                            : 'text-rose-600'
                                    }`}>
                                        {formatCurrency((total_deposits || summary.received) - (total_withdrawals || summary.paid))}
                                    </div>
                                    <p className="text-xs text-gray-500 mt-2">Received - Paid</p>
                                </div>
                            </div>

                            {/* Data Table */}
                            <div className="bg-white overflow-hidden shadow-sm rounded-xl">
                                <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                                    <h3 className="text-lg font-medium text-gray-900">
                                        Transaction History ({transactions.length})
                                    </h3>
                                    <span className="text-sm text-gray-500">Source: {filename}</span>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                        <tr>
                                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Type</th>
                                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                            <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Amount</th>
                                        </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                        {transactions.map((t, idx) => (
                                            <tr key={idx} className="hover:bg-gray-50 transition-colors">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full capitalize
                                                            ${t.type === 'received'
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-red-100 text-red-800'}`}>
                                                            {t.type}
                                                        </span>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-900">
                                                    {t.description}
                                                </td>
                                                <td className={`px-6 py-4 whitespace-nowrap text-sm text-right font-medium
                                                        ${t.type === 'received' ? 'text-emerald-600' : 'text-rose-600'}`}>
                                                    {t.type === 'received' ? '+' : '-'}
                                                    {formatCurrency(t.amount)}
                                                </td>
                                            </tr>
                                        ))}
                                        </tbody>
                                        {/* Table Footer with Statement Totals */}
                                        {total_deposits && (
                                            <tfoot className="bg-gray-50 border-t-2 border-gray-300">
                                            <tr className="font-semibold">
                                                <td colSpan="2" className="px-6 py-4 text-sm text-gray-900">
                                                    Statement Totals (from footer)
                                                </td>
                                                <td className="px-6 py-4 text-right text-sm">
                                                    <div className="space-y-1">
                                                        <div className="text-emerald-600">
                                                            +{formatCurrency(total_deposits)}
                                                        </div>
                                                        <div className="text-rose-600">
                                                            -{formatCurrency(total_withdrawals)}
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            </tfoot>
                                        )}
                                    </table>
                                </div>
                                {transactions.length === 0 && (
                                    <div className="p-12 text-center text-gray-500">
                                        No transactions found in this document.
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
