import React, { useState, useEffect, useRef } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';

export default function CreateInvoice({ auth }) {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [conversationId, setConversationId] = useState(null);

    const [invoicePreview, setInvoicePreview] = useState({
        seller_company_name: '',
        seller_gst_number: '',
        seller_state: '',
        seller_state_code: '',
        client_name: '',
        client_email: '',
        client_gst_number: '',
        client_state: '',
        client_state_code: '',
        invoice_date: '',
        line_items: [],
        subtotal: 0,
        cgst: 0,
        sgst: 0,
        total: 0
    });

    const messagesEndRef = useRef(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages, isLoading]);

    const handleSend = async (messageText) => {
        if (!messageText.trim() || isLoading) return;

        const userMessage = { role: 'user', content: messageText };
        setMessages(prev => [...prev, userMessage]);

        setInput('');
        setIsLoading(true);

        try {
            const response = await axios.post('/invoices/chat', {
                message: messageText,
                conversation_id: conversationId,
            }, { timeout: 120000 });

            const data = response.data;

            if (data.conversation_id && !conversationId) {
                setConversationId(data.conversation_id);
            }

            if (data.invoice_data) {
                updatePreviewData(data.invoice_data);
            }

            setMessages(prev => [...prev, {
                role: 'assistant',
                content: data.response,
                pdf_url: data.pdf_url || null,
                invoice_number: data.invoice_number || null
            }]);

        } catch (error) {
            console.error('Error:', error);
            setMessages(prev => [...prev, {
                role: 'error',
                content: 'I encountered a connection error. Please try again.'
            }]);
        } finally {
            setIsLoading(false);
        }
    };

    const onSubmitForm = (e) => {
        e.preventDefault();
        handleSend(input);
    };

    const updatePreviewData = (data) => {
        let items = [];
        try {
            items = typeof data.line_items_json === 'string'
                ? JSON.parse(data.line_items_json)
                : (data.line_items || []);
        } catch (e) { items = []; }

        const calculatedSubtotal = items.reduce((acc, item) => {
            const qty = parseFloat(item.quantity) || 0;
            const rate = parseFloat(item.rate) || 0;
            return acc + (qty * rate);
        }, 0);

        const subtotal = parseFloat(data.subtotal) || calculatedSubtotal;
        const cgst = subtotal * 0.09;
        const sgst = subtotal * 0.09;
        const total = subtotal + cgst + sgst;

        setInvoicePreview({
            seller_company_name: data.seller_company_name || '',
            seller_gst_number: data.seller_gst_number || '',
            seller_state: data.seller_state || '',
            seller_state_code: data.seller_state_code || '',
            client_name: data.client_name || '',
            client_email: data.client_email || '',
            client_gst_number: data.client_gst_number || '',
            client_state: data.client_state || '',
            client_state_code: data.client_state_code || '',
            invoice_date: data.invoice_date || '',
            line_items: items,
            subtotal: subtotal,
            cgst: cgst,
            sgst: sgst,
            total: total
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Create GST Invoice
                </h2>
            }
        >
            <Head title="Create GST Invoice" />

            <div className="py-6 h-[calc(100vh-80px)]">
                <div className="mx-auto max-w-7xl h-full sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg h-full border border-gray-200 flex flex-col md:flex-row">

                        {/* LEFT COLUMN: Chat Interface */}
                        <div className="flex-1 flex flex-col h-full border-r border-gray-200">

                            {/* Internal Header */}
                            <div className="p-4 border-b border-gray-100 bg-white flex justify-between items-center shadow-sm z-10">
                                <div className="flex items-center gap-3">
                                    <div className="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    </div>
                                    <div>
                                        <h3 className="font-bold text-gray-800 text-sm">Invoice Assistant</h3>
                                        <p className="text-xs text-gray-500">Always active and ready to draft</p>
                                    </div>
                                </div>
                            </div>

                            {/* Messages Area */}
                            <div className="flex-1 overflow-y-auto p-6 space-y-6 bg-gray-50/30">
                                {messages.length === 0 && (
                                    <div className="flex flex-col items-center justify-center h-full text-center max-w-md mx-auto animate-fade-in-up">
                                        <div className="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6">
                                            <svg className="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <h3 className="text-xl font-bold text-gray-800 mb-2">Let's draft an invoice.</h3>
                                        <p className="text-sm text-gray-500 mb-8 leading-relaxed">
                                            I can help you look up clients, add line items, and apply the correct GST automatically. How would you like to start?
                                        </p>

                                        {/* Quick Action Chips */}
                                        <div className="flex flex-col gap-3 w-full">
                                            <button onClick={() => handleSend("Get me the client list")} className="px-4 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-blue-300 transition-all shadow-sm flex items-center justify-between group">
                                                <span className="flex items-center gap-2"><svg className="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg> Get me the client list</span>
                                                <svg className="w-4 h-4 text-gray-300 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg>
                                            </button>
                                            <button onClick={() => handleSend("Create a new draft for Walk-in Customer")} className="px-4 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-blue-300 transition-all shadow-sm flex items-center justify-between group">
                                                <span className="flex items-center gap-2"><svg className="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Draft for Walk-in Customer</span>
                                                <svg className="w-4 h-4 text-gray-300 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                )}

                                {messages.map((msg, idx) => (
                                    <div key={idx} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                                        <div className={`max-w-[95%] rounded-2xl p-4 shadow-sm ${
                                            msg.role === 'user'
                                                ? 'bg-blue-600 text-white rounded-br-none'
                                                : msg.role === 'error'
                                                    ? 'bg-red-50 text-red-700 border border-red-200'
                                                    : 'bg-white border border-gray-100 text-gray-800 rounded-bl-none'
                                        }`}>

                                            {/* Professional Markdown Renderer */}
                                            <div className={`text-sm leading-relaxed ${msg.role === 'user' ? 'text-white' : 'text-gray-700'}`}>
                                                {msg.role === 'user' ? (
                                                    msg.content
                                                ) : (
                                                    <ReactMarkdown
                                                        remarkPlugins={[remarkGfm]}
                                                        components={{
                                                            table: ({node, ...props}) => <div className="overflow-x-auto my-3"><table className="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg text-xs" {...props} /></div>,
                                                            th: ({node, ...props}) => <th className="bg-gray-50 px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" {...props} />,
                                                            td: ({node, ...props}) => <td className="px-3 py-2 whitespace-nowrap text-xs text-gray-600 border-b border-gray-100" {...props} />,
                                                            strong: ({node, ...props}) => <strong className="font-semibold text-gray-900" {...props} />,
                                                            ul: ({node, ...props}) => <ul className="list-disc pl-5 my-2 space-y-1" {...props} />,
                                                            li: ({node, ...props}) => <li className="" {...props} />
                                                        }}
                                                    >
                                                        {msg.content}
                                                    </ReactMarkdown>
                                                )}
                                            </div>

                                            {msg.pdf_url && (
                                                <div className="mt-4 bg-gray-50 border border-gray-100 rounded-xl p-3 flex items-center gap-3">
                                                    <div className="h-10 w-10 bg-white rounded-lg flex items-center justify-center text-red-500 shadow-sm border border-gray-100">
                                                        <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/>
                                                        </svg>
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="font-semibold text-sm text-gray-900 truncate">Final Invoice Ready</p>
                                                        <p className="text-xs text-gray-500">{msg.invoice_number}</p>
                                                    </div>
                                                    <a
                                                        href={msg.pdf_url}
                                                        download
                                                        className="px-4 py-2 bg-gray-900 text-white text-xs font-bold rounded-lg shadow-sm hover:bg-black transition flex items-center gap-2"
                                                    >
                                                        <span>Download PDF</span>
                                                        <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                    </a>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}

                                {isLoading && (
                                    <div className="flex justify-start">
                                        <div className="bg-white border border-gray-100 rounded-2xl rounded-bl-none p-4 shadow-sm flex items-center gap-3">
                                            <div className="flex space-x-1.5">
                                                <div className="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style={{ animationDelay: '0ms' }}></div>
                                                <div className="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }}></div>
                                                <div className="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style={{ animationDelay: '300ms' }}></div>
                                            </div>
                                            <span className="text-xs text-gray-500 font-medium">Processing...</span>
                                        </div>
                                    </div>
                                )}
                                <div ref={messagesEndRef} />
                            </div>

                            {/* Input Area */}
                            <div className="p-4 bg-white border-t border-gray-100 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.02)]">
                                <form onSubmit={onSubmitForm} className="relative flex items-center gap-2">
                                    <input
                                        type="text"
                                        value={input}
                                        onChange={(e) => setInput(e.target.value)}
                                        placeholder="Type your reply (e.g., 'Add 5 chairs')..."
                                        className="w-full pl-4 pr-12 py-3.5 bg-gray-50 border border-gray-200 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-inner"
                                        disabled={isLoading}
                                        autoFocus
                                    />
                                    <button
                                        type="submit"
                                        disabled={isLoading || !input.trim()}
                                        className="absolute right-2 p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors shadow-sm"
                                    >
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                    </button>
                                </form>
                                <div className="text-center mt-2">
                                    <span className="text-[10px] text-gray-400">AI can make mistakes. Verify the preview before finalizing.</span>
                                </div>
                            </div>
                        </div>

                        {/* RIGHT COLUMN: Live GST Invoice Preview (Unchanged) */}
                        <div className="hidden md:block w-[450px] bg-gray-50 overflow-y-auto p-6 border-l border-gray-200">
                            <div className="sticky top-0">
                                <h3 className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                    <span className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                    Live Draft Preview
                                </h3>

                                <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-h-[500px] relative text-sm">
                                    {!invoicePreview.client_name && (
                                        <div className="absolute inset-0 flex items-center justify-center opacity-5 pointer-events-none">
                                            <span className="text-4xl font-black text-gray-900 -rotate-12">DRAFT</span>
                                        </div>
                                    )}

                                    {/* Header */}
                                    <div className="flex justify-between items-start mb-6 border-b pb-4">
                                        <div>
                                            <h1 className="text-lg font-bold text-gray-900">TAX INVOICE</h1>
                                            <p className="text-xs text-gray-500">Original for Recipient</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-xs text-gray-500">Invoice No.</p>
                                            <p className="font-bold">INV-{new Date().getTime().toString().substr(-6)}</p>
                                        </div>
                                    </div>

                                    {/* Addresses */}
                                    <div className="grid grid-cols-1 gap-6 mb-6">
                                        <div className="text-xs">
                                            <p className="font-bold text-gray-400 uppercase mb-1">Billed By</p>
                                            {invoicePreview.seller_company_name ? (
                                                <div className="text-gray-900">
                                                    <p className="font-bold text-sm">{invoicePreview.seller_company_name}</p>
                                                    <p>GSTIN: {invoicePreview.seller_gst_number}</p>
                                                    <p>{invoicePreview.seller_state} ({invoicePreview.seller_state_code})</p>
                                                </div>
                                            ) : <span className="text-gray-300 italic">Waiting for data...</span>}
                                        </div>

                                        <div className="text-xs">
                                            <p className="font-bold text-gray-400 uppercase mb-1">Billed To</p>
                                            {invoicePreview.client_name ? (
                                                <div className="text-gray-900">
                                                    <p className="font-bold text-sm">{invoicePreview.client_name}</p>
                                                    <p className="text-gray-500">{invoicePreview.client_email}</p>
                                                    <p>GSTIN: {invoicePreview.client_gst_number}</p>
                                                    <p>{invoicePreview.client_state} ({invoicePreview.client_state_code})</p>
                                                </div>
                                            ) : <span className="text-gray-300 italic">Waiting for data...</span>}
                                        </div>
                                    </div>

                                    {/* Items Table */}
                                    <div className="mb-6 border border-gray-200 rounded-lg overflow-hidden">
                                        <table className="w-full text-left text-xs">
                                            <thead className="bg-gray-50 border-b border-gray-200">
                                            <tr>
                                                <th className="py-2 pl-3 font-semibold text-gray-600 border-r border-gray-200">Item</th>
                                                <th className="py-2 text-center font-semibold text-gray-600 border-r border-gray-200 w-16">HSN</th>
                                                <th className="py-2 text-right font-semibold text-gray-600 border-r border-gray-200 w-16">Qty</th>
                                                <th className="py-2 text-right font-semibold text-gray-600 border-r border-gray-200 w-24">Rate</th>
                                                <th className="py-2 pr-3 text-right font-semibold text-gray-600 w-24">Amount</th>
                                            </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-100">
                                            {invoicePreview.line_items.map((item, i) => (
                                                <tr key={i}>
                                                    <td className="py-2 pl-3 text-gray-800 border-r border-gray-200">{item.description}</td>
                                                    <td className="py-2 text-center text-gray-500 border-r border-gray-200">{item.hsn_code || '-'}</td>
                                                    <td className="py-2 text-right text-gray-500 border-r border-gray-200">{item.quantity}</td>
                                                    <td className="py-2 text-right text-gray-500 border-r border-gray-200">₹{parseFloat(item.rate).toFixed(2)}</td>
                                                    <td className="py-2 pr-3 text-right font-medium text-gray-800">₹{(item.quantity * item.rate).toFixed(2)}</td>
                                                </tr>
                                            ))}
                                            {invoicePreview.line_items.length === 0 && (
                                                <tr>
                                                    <td colSpan="5" className="py-8 text-center text-gray-300 italic text-xs">
                                                        Items will appear here...
                                                    </td>
                                                </tr>
                                            )}
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* Calculated Totals */}
                                    <div className="border-t border-gray-100 pt-4">
                                        <div className="flex justify-between items-center mb-1 text-xs">
                                            <span className="text-gray-500">Subtotal</span>
                                            <span className="font-medium text-gray-900">₹{invoicePreview.subtotal.toFixed(2)}</span>
                                        </div>
                                        <div className="flex justify-between items-center mb-1 text-xs">
                                            <span className="text-gray-500">CGST (9%)</span>
                                            <span className="font-medium text-gray-900">₹{invoicePreview.cgst.toFixed(2)}</span>
                                        </div>
                                        <div className="flex justify-between items-center mb-3 text-xs">
                                            <span className="text-gray-500">SGST (9%)</span>
                                            <span className="font-medium text-gray-900">₹{invoicePreview.sgst.toFixed(2)}</span>
                                        </div>
                                        <div className="flex justify-between items-center pt-3 border-t border-dashed border-gray-200">
                                            <span className="font-bold text-gray-900 text-sm">Total</span>
                                            <span className="font-bold text-blue-600 text-lg">
                                                ₹{invoicePreview.total.toFixed(2)}
                                            </span>
                                        </div>
                                    </div>

                                    <div className="mt-6 pt-4 border-t border-gray-100 text-[10px] text-gray-400 text-center">
                                        This is a computer generated draft.
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
