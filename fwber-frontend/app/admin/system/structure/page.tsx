'use client';

import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Folder, FileText, Code, Database } from 'lucide-react';

export default function ProjectStructurePage() {
  const [structure, setStructure] = useState<any>(null);

  useEffect(() => {
    // In a real app, fetch this from an API endpoint that reads the file system
    // For now, we'll hardcode the structure based on the documentation
    setStructure({
      root: [
        { name: 'VERSION', type: 'file', desc: 'Global version file' },
        { name: 'docs/', type: 'dir', children: [
          { name: 'universal/', type: 'dir', desc: 'Master LLM Instructions' },
          { name: 'dashboard/', type: 'dir', desc: 'Project Reports' },
        ]},
        { name: 'fwber-backend/', type: 'dir', desc: 'Laravel API', icon: <Code className="w-4 h-4 text-red-500" /> },
        { name: 'fwber-frontend/', type: 'dir', desc: 'Next.js App', icon: <Code className="w-4 h-4 text-blue-500" /> },
        { name: 'docker/', type: 'dir', desc: 'Infrastructure (K8s)', icon: <Database className="w-4 h-4 text-green-500" /> },
      ]
    });
  }, []);

  if (!structure) return <div>Loading...</div>;

  const renderTree = (nodes: any[], depth = 0) => {
    return (
      <ul className={`space-y-2 ${depth > 0 ? 'ml-6 border-l pl-4 border-gray-200 dark:border-gray-700' : ''}`}>
        {nodes.map((node, i) => (
          <li key={i}>
            <div className="flex items-center gap-2 py-1">
              {node.icon || (node.type === 'dir' ? <Folder className="w-4 h-4 text-amber-500" /> : <FileText className="w-4 h-4 text-gray-400" />)}
              <span className="font-mono text-sm">{node.name}</span>
              {node.desc && <span className="text-xs text-muted-foreground ml-2">- {node.desc}</span>}
            </div>
            {node.children && renderTree(node.children, depth + 1)}
          </li>
        ))}
      </ul>
    );
  };

  return (
    <div className="p-6 max-w-4xl mx-auto space-y-6">
      <h1 className="text-2xl font-bold">Project Structure Dashboard</h1>
      <Card>
        <CardHeader>
          <CardTitle>Directory Layout (v0.3.36)</CardTitle>
        </CardHeader>
        <CardContent>
          {renderTree(structure.root)}
        </CardContent>
      </Card>
    </div>
  );
}
