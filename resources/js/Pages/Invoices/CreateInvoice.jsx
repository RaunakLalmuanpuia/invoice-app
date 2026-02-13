import React, { useState, useEffect, useRef } from 'react';
import { Head } from '@inertiajs/react';
import axios from 'axios';

export default function CreateInvoice() {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [conversationId, setConversationId] = useState(null);

    // Enhanced state for GST invoice preview
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

    const sendMessage = async (e) => {
        e.preventDefault();
        if (!input.trim() || isLoading) return;

        const userMessage = { role: 'user', content: input };
        setMessages(prev => [...prev, userMessage]);

        const currentInput = input;
        setInput('');
        setIsLoading(true);

        try {
            const response = await axios.post('/invoices/chat', {
                message: currentInput,
                conversation_id: conversationId,
            });

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

    const updatePreviewData = (data) => {
        let items = [];
        try {
            items = typeof data.line_items_json === 'string'
                ? JSON.parse(data.line_items_json)
                : (data.line_items || []);
        } catch (e) { items = []; }

        // Calculate GST breakdown
        const subtotal = data.subtotal || 0;
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
        <>
            <Head title="Create GST Invoice" />

            <div className="min-h-screen bg-gray-50 flex flex-col md:flex-row">

                {/* LEFT COLUMN: Chat Interface */}
                <div className="flex-1 flex flex-col h-screen max-w-4xl mx-auto w-full border-r border-gray-200 bg-white">

                    {/* Header */}
                    <div className="p-4 border-b border-gray-100 flex justify-between items-center bg-white">
                        <div>
                            <h2 className="text-lg font-bold text-gray-800">GST Invoice Assistant</h2>
                            <p className="text-xs text-gray-500">Create GST-Compliant Tax Invoices</p>
                        </div>
                    </div>

                    {/* Messages Area */}
                    <div className="flex-1 overflow-y-auto p-4 space-y-6 bg-gray-50/50">
                        {messages.length === 0 && (
                            <div className="text-center mt-20 opacity-60">
                                <div className="bg-blue-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg className="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 className="text-gray-800 font-medium">Create a new GST Tax Invoice</h3>
                                <p className="text-sm text-gray-500 mt-1">Try saying "Create a GST invoice"</p>
                            </div>
                        )}

                        {messages.map((msg, idx) => (
                            <div key={idx} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                                <div className={`max-w-[85%] rounded-2xl p-4 shadow-sm ${
                                    msg.role === 'user'
                                        ? 'bg-blue-600 text-white rounded-br-none'
                                        : msg.role === 'error'
                                            ? 'bg-red-50 text-red-700 border border-red-200'
                                            : 'bg-white text-gray-800 border border-gray-100 rounded-bl-none'
                                }`}>

                                    <div className="whitespace-pre-wrap text-sm leading-relaxed">
                                        {msg.content}
                                    </div>

                                    {/* PDF Download Card */}
                                    {msg.pdf_url && (
                                        <div className="mt-4 bg-gray-50 border border-gray-200 rounded-xl p-4 flex items-center gap-4 group hover:border-blue-300 transition-colors">
                                            <div className="h-12 w-12 bg-white rounded-lg border border-gray-200 flex items-center justify-center shadow-sm text-red-500">
                                                <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/>
                                                </svg>
                                            </div>
                                            <div className="flex-1">
                                                <h4 className="font-semibold text-gray-900 text-sm">GST Tax Invoice Generated</h4>
                                                <p className="text-xs text-gray-500">{msg.invoice_number}</p>
                                            </div>
                                            <a
                                                href={msg.pdf_url}
                                                download
                                                className="px-4 py-2 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-lg transition shadow-md flex items-center gap-2"
                                            >
                                                <span>Download</span>
                                                <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            </a>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}

                        {/* Loading Indicator */}
                        {isLoading && (
                            <div className="flex justify-start">
                                <div className="bg-white border border-gray-100 rounded-2xl rounded-bl-none p-4 shadow-sm flex items-center gap-2">
                                    <div className="flex space-x-1">
                                        <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0ms' }}></div>
                                        <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }}></div>
                                        <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '300ms' }}></div>
                                    </div>
                                    <span className="text-xs text-gray-400 font-medium">Processing...</span>
                                </div>
                            </div>
                        )}
                        <div ref={messagesEndRef} />
                    </div>

                    {/* Input Area */}
                    <div className="p-4 bg-white border-t border-gray-100">
                        <form onSubmit={sendMessage} className="relative flex items-center gap-2">
                            <input
                                type="text"
                                value={input}
                                onChange={(e) => setInput(e.target.value)}
                                placeholder="Type your message..."
                                className="w-full pl-4 pr-12 py-3 bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm"
                                disabled={isLoading}
                                autoFocus
                            />
                            <button
                                type="submit"
                                disabled={isLoading || !input.trim()}
                                className="absolute right-2 p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:hover:bg-blue-600 transition-colors"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                {/* RIGHT COLUMN: Live GST Invoice Preview */}
                <div className="hidden md:block w-96 bg-gray-100 border-l border-gray-200 overflow-y-auto p-6">
                    <div className="sticky top-6">
                        <h3 className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Live GST Invoice Preview</h3>

                        {/* GST Invoice Preview Card */}
                        <div className="bg-white p-6 rounded-lg shadow-md min-h-[500px] border border-gray-300 relative text-sm">

                            {/* Watermark if empty */}
                            {!invoicePreview.client_name && (
                                <div className="absolute inset-0 flex items-center justify-center opacity-10 pointer-events-none">
                                    <span className="text-4xl font-bold text-gray-400 rotate-45">DRAFT</span>
                                </div>
                            )}

                            {/* Tax Invoice Header */}
                            <div className="text-center text-lg font-bold mb-4 pb-3 border-b-2 border-gray-300">
                                TAX INVOICE
                            </div>

                            {/* Seller Info */}
                            <div className="mb-4 p-3 bg-gray-50 border border-gray-200 rounded">
                                <div className="text-xs text-gray-500 mb-1">From (Seller):</div>
                                {invoicePreview.seller_company_name ? (
                                    <>
                                        <div className="font-bold text-gray-900">{invoicePreview.seller_company_name}</div>
                                        {invoicePreview.seller_gst_number && (
                                            <div className="text-xs text-gray-600">GSTIN: {invoicePreview.seller_gst_number}</div>
                                        )}
                                        {invoicePreview.seller_state && (
                                            <div className="text-xs text-gray-600">
                                                State: {invoicePreview.seller_state}
                                                {invoicePreview.seller_state_code && `, Code: ${invoicePreview.seller_state_code}`}
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    <div className="text-gray-300 italic">Company details pending...</div>
                                )}
                            </div>

                            {/* Buyer Info */}
                            <div className="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
                                <div className="text-xs text-gray-500 mb-1">To (Buyer):</div>
                                {invoicePreview.client_name ? (
                                    <>
                                        <div className="font-bold text-gray-900">{invoicePreview.client_name}</div>
                                        <div className="text-xs text-gray-600">{invoicePreview.client_email}</div>
                                        {invoicePreview.client_gst_number && (
                                            <div className="text-xs text-gray-600">GSTIN: {invoicePreview.client_gst_number}</div>
                                        )}
                                        {invoicePreview.client_state && (
                                            <div className="text-xs text-gray-600">
                                                State: {invoicePreview.client_state}
                                                {invoicePreview.client_state_code && `, Code: ${invoicePreview.client_state_code}`}
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    <div className="text-gray-300 italic">Client details pending...</div>
                                )}
                            </div>

                            {/* Invoice Details */}
                            <div className="grid grid-cols-2 gap-4 mb-4 text-xs">
                                <div>
                                    <div className="text-gray-500">Invoice Date</div>
                                    <div className="font-medium">{invoicePreview.invoice_date || '-'}</div>
                                </div>
                                <div>
                                    <div className="text-gray-500">Invoice No.</div>
                                    <div className="font-medium">INV-{new Date().getTime().toString().substr(-6)}</div>
                                </div>
                            </div>

                            {/* Line Items Table */}
                            <div className="mb-4 border border-gray-200 rounded">
                                <table className="w-full text-left text-xs">
                                    <thead className="bg-gray-100">
                                    <tr className="border-b border-gray-200">
                                        <th className="py-2 px-2 font-medium">Description</th>
                                        <th className="py-2 px-2 text-center font-medium">HSN</th>
                                        <th className="py-2 px-2 text-right font-medium">Qty</th>
                                        <th className="py-2 px-2 text-right font-medium">Rate</th>
                                        <th className="py-2 px-2 text-right font-medium">Amount</th>
                                    </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                    {invoicePreview.line_items.length > 0 ? (
                                        invoicePreview.line_items.map((item, i) => (
                                            <tr key={i}>
                                                <td className="py-2 px-2">
                                                    <div className="font-medium text-gray-800">{item.description}</div>
                                                </td>
                                                <td className="py-2 px-2 text-center text-gray-600">
                                                    {item.hsn_code || '-'}
                                                </td>
                                                <td className="py-2 px-2 text-right text-gray-600">
                                                    {item.quantity} {item.unit || 'Nos'}
                                                </td>
                                                <td className="py-2 px-2 text-right text-gray-600">
                                                    ₹{item.rate?.toFixed(2)}
                                                </td>
                                                <td className="py-2 px-2 text-right text-gray-800 font-medium">
                                                    ₹{(item.quantity * item.rate).toFixed(2)}
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="5" className="py-4 text-center text-gray-300 italic">
                                                No items added yet
                                            </td>
                                        </tr>
                                    )}
                                    </tbody>
                                </table>
                            </div>

                            {/* GST Tax Breakdown */}
                            <div className="border-t-2 border-gray-300 pt-3">
                                <div className="flex justify-between items-center mb-1 text-xs">
                                    <span className="text-gray-600">Subtotal</span>
                                    <span className="font-medium">₹{invoicePreview.subtotal.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between items-center mb-1 text-xs">
                                    <span className="text-gray-600">CGST @ 9%</span>
                                    <span className="font-medium">₹{invoicePreview.cgst.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between items-center mb-3 text-xs">
                                    <span className="text-gray-600">SGST @ 9%</span>
                                    <span className="font-medium">₹{invoicePreview.sgst.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between items-center pt-3 border-t-2 border-gray-300">
                                    <span className="font-bold text-gray-900">Total Amount</span>
                                    <span className="font-bold text-blue-600 text-lg">
                                        ₹{invoicePreview.total.toFixed(2)}
                                    </span>
                                </div>
                            </div>

                            {/* GST Compliance Note */}
                            {invoicePreview.line_items.length > 0 && (
                                <div className="mt-4 p-2 bg-green-50 border border-green-200 rounded text-xs text-green-800">
                                    <div className="flex items-start gap-2">
                                        <svg className="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd"/>
                                        </svg>
                                        <span>GST-compliant invoice with CGST + SGST breakdown</span>
                                    </div>
                                </div>
                            )}

                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
