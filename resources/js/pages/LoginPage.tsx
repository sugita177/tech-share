import React, { useState } from 'react';

const LoginPage: React.FC = () => {
    const [email, setEmail] = useState<string>('');
    const [password, setPassword] = useState<string>('');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        console.log('ログイン試行:', { email, password });
        // ここで後ほど axiosClient を使って API を叩きます
    };

    return (
        <div style={{ padding: '40px', maxWidth: '400px' }}>
            <h2>ログイン</h2>
            <form onSubmit={handleSubmit}>
                <div>
                    <label>Email:</label><br />
                    <input 
                        type="email" 
                        value={email} 
                        onChange={(e) => setEmail(e.target.value)} 
                    />
                </div>
                <div style={{ marginTop: '10px' }}>
                    <label>Password:</label><br />
                    <input 
                        type="password" 
                        value={password} 
                        onChange={(e) => setPassword(e.target.value)} 
                    />
                </div>
                <button type="submit" style={{ marginTop: '20px' }}>ログイン</button>
            </form>
        </div>
    );
};

export default LoginPage;