"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Server, Database, FolderGit2, Activity, ShieldCheck, Cpu } from "lucide-react";
import AppHeader from "@/components/AppHeader";
import ProtectedRoute from "@/components/ProtectedRoute";

interface SystemInfo {
  version: string;
  environment: string;
  backend_status: "online" | "offline";
  database_status: "connected" | "disconnected";
  submodules: {
    name: string;
    path: string;
    status: string;
    version?: string;
  }[];
}

// Mock data until real endpoint exists
const MOCK_SYSTEM_INFO: SystemInfo = {
  version: "0.3.33",
  environment: process.env.NODE_ENV || "development",
  backend_status: "online",
  database_status: "connected",
  submodules: [
    { name: "fwber-backend", path: "/fwber-backend", status: "active", version: "v0.3.33" },
    { name: "fwber-frontend", path: "/fwber-frontend", status: "active", version: "v0.3.33" }
  ]
};

export default function SystemDashboardPage() {
  const [info, setInfo] = useState<SystemInfo>(MOCK_SYSTEM_INFO);
  const [loading, setLoading] = useState(false); // Simulate loading if we had an API

  // Ideally fetch from /api/admin/system-health
  useEffect(() => {
    // In a real app, we'd fetch this from the backend
    setInfo(MOCK_SYSTEM_INFO);
  }, []);

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <AppHeader />

        <main className="max-w-7xl mx-auto px-4 py-8">
          <div className="flex items-center justify-between mb-8">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <Cpu className="w-8 h-8 text-blue-600" />
                System Dashboard
              </h1>
              <p className="text-gray-500 dark:text-gray-400 mt-1">
                Monitor system health, versions, and project structure.
              </p>
            </div>
            <div className="flex items-center gap-3">
              <Badge variant="outline" className="px-3 py-1 text-sm bg-white dark:bg-gray-800">
                v{info.version}
              </Badge>
              <Badge className={info.backend_status === "online" ? "bg-green-500" : "bg-red-500"}>
                {info.backend_status === "online" ? "System Operational" : "System Issues"}
              </Badge>
            </div>
          </div>

          <Tabs defaultValue="overview" className="space-y-6">
            <TabsList>
              <TabsTrigger value="overview">Overview</TabsTrigger>
              <TabsTrigger value="structure">Project Structure</TabsTrigger>
              <TabsTrigger value="changelog">Changelog</TabsTrigger>
            </TabsList>

            <TabsContent value="overview" className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <StatusCard
                  title="Backend API"
                  status={info.backend_status}
                  icon={<Server className="w-5 h-5" />}
                  detail="Laravel 12 (PHP 8.4)"
                />
                <StatusCard
                  title="Database"
                  status={info.database_status === "connected" ? "online" : "offline"}
                  icon={<Database className="w-5 h-5" />}
                  detail="MySQL 8.0"
                />
                <StatusCard
                  title="Frontend"
                  status="online"
                  icon={<Activity className="w-5 h-5" />}
                  detail="Next.js 16 (React 18)"
                />
                <StatusCard
                  title="Security"
                  status="online"
                  icon={<ShieldCheck className="w-5 h-5" />}
                  detail="E2E Encrypted"
                />
              </div>

              <Card>
                <CardHeader>
                  <CardTitle>Submodules & Packages</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="relative overflow-x-auto">
                    <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                      <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                          <th scope="col" className="px-6 py-3">Module Name</th>
                          <th scope="col" className="px-6 py-3">Path</th>
                          <th scope="col" className="px-6 py-3">Version</th>
                          <th scope="col" className="px-6 py-3">Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        {info.submodules.map((mod, i) => (
                          <tr key={i} className="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" className="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white flex items-center gap-2">
                              <FolderGit2 className="w-4 h-4" />
                              {mod.name}
                            </th>
                            <td className="px-6 py-4 font-mono text-xs">{mod.path}</td>
                            <td className="px-6 py-4">{mod.version}</td>
                            <td className="px-6 py-4">
                              <Badge variant="secondary" className="capitalize">{mod.status}</Badge>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="structure">
              <Card>
                <CardHeader>
                  <CardTitle>Project Directory Structure</CardTitle>
                </CardHeader>
                <CardContent className="font-mono text-sm bg-gray-900 text-gray-300 p-6 rounded-lg overflow-x-auto">
                  <pre>{`
/ (Project Root)
├── fwber-backend/          # Laravel API Source
│   ├── app/                # Core Logic (Controllers, Models)
│   ├── routes/             # API Definitions
│   ├── tests/              # Feature & Unit Tests
│   └── database/           # Migrations & Seeds
│
├── fwber-frontend/         # Next.js Application
│   ├── app/                # App Router Pages
│   │   ├── (auth)/         # Authentication Routes
│   │   ├── admin/          # Admin Dashboard
│   │   ├── dashboard/      # User Dashboard
│   │   └── ...             # Feature Pages
│   ├── components/         # Reusable UI Components
│   ├── lib/                # Utilities & Hooks
│   └── public/             # Static Assets
│
├── docs/                   # Documentation
│   ├── PROJECT_STATUS.md   # Current State
│   └── ROADMAP.md          # Future Plans
│
└── docker-compose.yml      # Orchestration
                  `}</pre>
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="changelog">
              <Card>
                <CardHeader>
                  <CardTitle>Recent Updates (v0.3.33)</CardTitle>
                </CardHeader>
                <CardContent className="prose dark:prose-invert max-w-none">
                  <h3>Frontend Feature Completion</h3>
                  <ul>
                    <li><strong>Achievements UI:</strong> Full interface for gamification tracking.</li>
                    <li><strong>Help Center:</strong> Comprehensive documentation portal.</li>
                    <li><strong>Security Settings:</strong> E2E key management UI.</li>
                    <li><strong>Proximity Features:</strong> Token-gated chatrooms & paid photo reveals.</li>
                  </ul>
                  <h3>Refactoring</h3>
                  <ul>
                    <li>Unified Real-time logic (removed legacy Mercure hooks).</li>
                    <li>Standardized API client usage.</li>
                    <li>Comprehensive linting & type fixes.</li>
                  </ul>
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </main>
      </div>
    </ProtectedRoute>
  );
}

function StatusCard({ title, status, icon, detail }: { title: string, status: string, icon: React.ReactNode, detail: string }) {
  const isOnline = status === "online";
  return (
    <Card>
      <CardContent className="p-6">
        <div className="flex items-center justify-between mb-4">
          <div className={`p-3 rounded-full ${isOnline ? "bg-green-100 text-green-600" : "bg-red-100 text-red-600"}`}>
            {icon}
          </div>
          <span className={`flex h-3 w-3 rounded-full ${isOnline ? "bg-green-500" : "bg-red-500"}`} />
        </div>
        <div className="space-y-1">
          <h3 className="font-semibold text-lg">{title}</h3>
          <p className="text-sm text-gray-500 capitalize">{status}</p>
          <p className="text-xs text-gray-400 font-mono mt-2">{detail}</p>
        </div>
      </CardContent>
    </Card>
  );
}
