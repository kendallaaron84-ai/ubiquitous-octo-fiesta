import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';

const JubileePlayerApp = ({ assetId, studioKey, config }) => {
    const [status, setStatus] = useState('loading'); // loading, ready, error
    const [productData, setProductData] = useState(null);

    useEffect(() => {
        const fetchSaaSData = async () => {
            try {
                // 1. Ping the Command Center API directly
                const response = await fetch(`${config.endpoints.publicProduct}?assetId=${assetId}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Studio-Key': studioKey // Secure Tenant ID Gateway
                    }
                });

                if (!response.ok) {
                    throw new Error(response.status === 403 ? "Tenant Key Invalid" : "Asset Not Found");
                }

                const data = await response.json();
                setProductData(data);
                setStatus('ready');

            } catch (error) {
                console.error("Jubilee Cloud Error:", error);
                setStatus('error');
            }
        };

        fetchSaaSData();
    }, [assetId, studioKey, config]);

    // 2. Stripe Revenue Handler
    const handleCheckout = () => {
        // Redirect directly to the Next.js Stripe Checkout bridge
        window.location.href = `${config.endpoints.checkout}?assetId=${assetId}&tenantKey=${studioKey}`;
    };

    // 3. Twilio SMS Handshake Trigger
    const handleUnlock = () => {
        if (window.openSMSVerificationModal) {
            window.openSMSVerificationModal(assetId, studioKey);
        } else {
            console.warn("SMS Modal module not loaded.");
        }
    };

    if (status === 'loading') {
        return (
            <div className="jubilee-loading-state" style={{ textAlign: 'center', padding: '50px', color: '#64748b' }}>
                <div className="jubilee-spinner"></div>
                <p>Authenticating Cloud Engine...</p>
            </div>
        );
    }

    if (status === 'error') {
        return (
            <div className="jubilee-error-state" style={{ textAlign: 'center', padding: '50px', background: '#fee2e2', color: '#991b1b', borderRadius: '8px' }}>
                <strong>Cloud Connection Failed.</strong> Ensure your WPStudioKey is active and the asset exists.
            </div>
        );
    }

    // 4. The Lightning-Fast UI
    return (
        <div className="jubilee-player-canvas" style={{ background: productData.theme?.backgroundColor || '#070a0f', borderRadius: '12px', padding: '40px', color: '#fff', textAlign: 'center', boxShadow: '0 10px 25px rgba(0,0,0,0.5)' }}>
            
            <img 
                src={productData.coverUrl} 
                alt={productData.title} 
                style={{ width: '250px', borderRadius: '8px', boxShadow: '0 4px 15px rgba(0,0,0,0.6)', marginBottom: '20px' }} 
            />
            
            <h2 style={{ margin: '0 0 10px 0', fontFamily: 'system-ui', fontSize: '24px' }}>{productData.title}</h2>
            <p style={{ color: '#94a3b8', margin: '0 0 30px 0' }}>By {productData.authorName}</p>

            <div style={{ display: 'flex', justifyContent: 'center', gap: '15px' }}>
                {productData.price > 0 ? (
                    <>
                        <button 
                            onClick={handleCheckout}
                            style={{ background: '#f97316', color: '#000', border: 'none', padding: '12px 24px', borderRadius: '6px', fontWeight: 'bold', cursor: 'pointer', fontSize: '16px' }}>
                            Buy for ${productData.price.toFixed(2)}
                        </button>
                        <button 
                            onClick={handleUnlock}
                            style={{ background: 'transparent', color: '#f97316', border: '1px solid #f97316', padding: '12px 24px', borderRadius: '6px', fontWeight: 'bold', cursor: 'pointer', fontSize: '16px' }}>
                            I already own this (Login)
                        </button>
                    </>
                ) : (
                    <button 
                        onClick={() => window.bootKobaPlayer()}
                        style={{ background: '#10b981', color: '#000', border: 'none', padding: '12px 30px', borderRadius: '6px', fontWeight: 'bold', cursor: 'pointer', fontSize: '16px' }}>
                        Stream Free Now
                    </button>
                )}
            </div>
        </div>
    );
};

// 5. The Agnostic Injector (Mounts automatically when script loads)
document.addEventListener("DOMContentLoaded", () => {
    const rootNode = document.getElementById("jubilee-bloom-root");
    
    if (rootNode) {
        const assetId = rootNode.getAttribute("data-asset");
        const studioKey = rootNode.getAttribute("data-studio-key");
        
        // Fallback config if the user pasted this into raw HTML without the localized script
        const config = window.JubileeConfig || {
            endpoints: {
                publicProduct: "https://dashboard.koba-i.com/api/products/public",
                checkout: "https://dashboard.koba-i.com/api/checkout"
            }
        };

        const root = createRoot(rootNode);
        root.render(<JubileePlayerApp assetId={assetId} studioKey={studioKey} config={config} />);
    }
});