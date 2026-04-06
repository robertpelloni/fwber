'use client';

import ProtectedRoute from '@/components/ProtectedRoute';
import { Activity, Server, Database, GitBranch, Folder, FileText, Layers } from 'lucide-react';

export default function ProjectDashboardPage() {
  const projectVersion = process.env.NEXT_PUBLIC_PROJECT_VERSION || 'Unknown';
  const backendVersion = process.env.NEXT_PUBLIC_BACKEND_VERSION || 'Unknown';
  const frontendVersion = process.env.NEXT_PUBLIC_FRONTEND_VERSION || 'Unknown';

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50">
        <header className="bg-white shadow">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <Activity className="h-8 w-8 text-indigo-600" />
                <div>
                  <h1 className="text-2xl font-bold text-gray-900">Project Dashboard</h1>
                  <p className="text-sm text-gray-500">System Architecture & Versioning</p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <a
                  href="/admin/settings"
                  className="rounded-md bg-white border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                  Back to Settings
                </a>
              </div>
            </div>
          </div>
        </header>

        <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {/* Versioning Section */}
          <section className="mb-8">
            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <GitBranch className="h-5 w-5" />
              Component Versions
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                <div className="flex items-center justify-between mb-2">
                  <span className="font-medium text-gray-900">Project Root</span>
                  <span className="text-xs font-medium bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded-full">
                    v{projectVersion}
                  </span>
                </div>
                <p className="text-sm text-gray-500">Orchestration & Docs</p>
                <div className="mt-4 text-xs text-gray-400 font-mono">Source: /VERSION</div>
              </div>

              <div className="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                <div className="flex items-center justify-between mb-2">
                  <span className="font-medium text-gray-900">Backend API</span>
                  <span className="text-xs font-medium bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">
                    v{backendVersion}
                  </span>
                </div>
                <p className="text-sm text-gray-500">Laravel 12 Framework</p>
                <div className="mt-4 text-xs text-gray-400 font-mono">Source: /fwber-backend/package.json</div>
              </div>

              <div className="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                <div className="flex items-center justify-between mb-2">
                  <span className="font-medium text-gray-900">Frontend App</span>
                  <span className="text-xs font-medium bg-green-100 text-green-800 px-2 py-0.5 rounded-full">
                    v{frontendVersion}
                  </span>
                </div>
                <p className="text-sm text-gray-500">Next.js 14 Framework</p>
                <div className="mt-4 text-xs text-gray-400 font-mono">Source: /fwber-frontend/package.json</div>
              </div>
            </div>
          </section>

          {/* Project Structure Section */}
          <section className="mb-8">
            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <Folder className="h-5 w-5" />
              Project Structure
            </h2>
            <div className="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
              <div className="p-6">
                <div className="space-y-6">
                  <div className="flex items-start gap-4">
                    <div className="p-2 bg-gray-100 rounded-lg">
                      <Layers className="h-6 w-6 text-gray-600" />
                    </div>
                    <div>
                      <h3 className="font-medium text-gray-900">Root Directory (/)</h3>
                      <p className="text-sm text-gray-500 mt-1">
                        Contains orchestration files (Docker), documentation, and deployment scripts.
                        Acts as the monorepo root.
                      </p>
                      <div className="mt-2 flex gap-2">
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                          docker-compose.yml
                        </span>
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                          deploy.sh
                        </span>
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                          VERSION
                        </span>
                      </div>
                    </div>
                  </div>

                  <div className="border-t border-gray-100 pt-6 flex items-start gap-4">
                    <div className="p-2 bg-blue-50 rounded-lg">
                      <Server className="h-6 w-6 text-blue-600" />
                    </div>
                    <div>
                      <h3 className="font-medium text-gray-900">Backend (/fwber-backend)</h3>
                      <p className="text-sm text-gray-500 mt-1">
                        Laravel API server handling business logic, database interactions, queues, and WebSocket broadcasting.
                      </p>
                      <div className="mt-2 flex gap-2">
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-800">
                          app/Http/Controllers
                        </span>
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-800">
                          routes/api.php
                        </span>
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-800">
                          config/
                        </span>
                      </div>
                    </div>
                  </div>

                  <div className="border-t border-gray-100 pt-6 flex items-start gap-4">
                    <div className="p-2 bg-green-50 rounded-lg">
                      <Database className="h-6 w-6 text-green-600" />
                    </div>
                    <div>
                      <h3 className="font-medium text-gray-900">Frontend (/fwber-frontend)</h3>
                      <p className="text-sm text-gray-500 mt-1">
                        Next.js application providing the user interface, PWA capabilities, and client-side logic.
                      </p>
                      <div className="mt-2 flex gap-2">
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-800">
                          app/
                        </span>
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-800">
                          components/
                        </span>
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-800">
                          lib/
                        </span>
                      </div>
                    </div>
                  </div>

                  <div className="border-t border-gray-100 pt-6 flex items-start gap-4">
                    <div className="p-2 bg-yellow-50 rounded-lg">
                      <FileText className="h-6 w-6 text-yellow-600" />
                    </div>
                    <div>
                      <h3 className="font-medium text-gray-900">Documentation (/docs)</h3>
                      <p className="text-sm text-gray-500 mt-1">
                        Centralized documentation for API, deployment, features, and AI agent protocols.
                      </p>
                      <div className="mt-2 flex gap-2">
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-50 text-yellow-800">
                          LLM_INSTRUCTIONS.md
                        </span>
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-50 text-yellow-800">
                          ROADMAP.md
                        </span>
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-50 text-yellow-800">
                          API_DOCS.md
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </main>
      </div>
    </ProtectedRoute>
  );
}
