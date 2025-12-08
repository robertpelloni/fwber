'use client'

import { useState } from 'react'

export default function TestAuthPage() {
  const [email, setEmail] = useState('test2@fwber.me')
  const [password, setPassword] = useState('password123')
  const [result, setResult] = useState<string>('')
  const [loading, setLoading] = useState(false)

  const testLogin = async () => {
    setLoading(true)
    setResult('Testing login...')
    
    try {
      const response = await fetch('http://localhost:8000/api/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      })

      const data = await response.json()
      
      if (response.ok) {
        setResult(`✅ Login successful! Token: ${data.token?.substring(0, 20)}...`)
        
        // Test protected endpoint
        const userResponse = await fetch('http://localhost:8000/api/user', {
          headers: {
            'Authorization': `Bearer ${data.token}`,
            'Accept': 'application/json',
          },
        })
        
        const userData = await userResponse.json()
        if (userResponse.ok) {
          setResult(prev => prev + `\n✅ User profile: ${userData.data?.email || userData.email}`)
        } else {
          setResult(prev => prev + `\n❌ User profile error: ${userData.message}`)
        }
      } else {
        setResult(`❌ Login failed: ${data.message}`)
      }
    } catch (error) {
      setResult(`❌ Error: ${error}`)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="p-8 max-w-2xl mx-auto">
      <h1 className="text-3xl font-bold mb-6">FWBer API Integration Test</h1>
      
      <div className="space-y-4">
        <div>
          <label className="block text-sm font-medium mb-2">Email:</label>
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            className="w-full p-2 border rounded"
          />
        </div>
        
        <div>
          <label className="block text-sm font-medium mb-2">Password:</label>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className="w-full p-2 border rounded"
          />
        </div>
        
        <button
          onClick={testLogin}
          disabled={loading}
          className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 disabled:opacity-50"
        >
          {loading ? 'Testing...' : 'Test Login & Profile Access'}
        </button>
        
        {result && (
          <div className="mt-4 p-4 bg-gray-100 rounded">
            <pre className="whitespace-pre-wrap text-sm">{result}</pre>
          </div>
        )}
      </div>
    </div>
  )
}
