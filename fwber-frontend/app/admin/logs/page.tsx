'use client';

import { useState, useEffect, useCallback } from 'react';
import { apiClient } from '@/lib/api/client';
import ProtectedRoute from '@/components/ProtectedRoute';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Loader2, FileText, RefreshCw, Download } from 'lucide-react';
import { ScrollArea } from '@/components/ui/scroll-area';

interface LogFile {
  name: string;
  size: number;
  updated_at: string;
}

interface LogContent {
  filename: string;
  content: string;
}

function formatBytes(bytes: number, decimals = 2) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

export default function AdminLogsPage() {
  const [logs, setLogs] = useState<LogFile[]>([]);
  const [selectedLog, setSelectedLog] = useState<string | null>(null);
  const [logContent, setLogContent] = useState<string | null>(null);
  const [isLoadingList, setIsLoadingList] = useState(true);
  const [isLoadingContent, setIsLoadingContent] = useState(false);

  const fetchLogs = useCallback(async () => {
    setIsLoadingList(true);
    try {
      const response = await apiClient.get<LogFile[]>('/admin/logs');
      setLogs(response.data);
      // Don't auto-select to save bandwidth
    } catch (error) {
      console.error('Failed to fetch logs', error);
    } finally {
      setIsLoadingList(false);
    }
  }, []);

  const fetchLogContent = async (filename: string) => {
    setIsLoadingContent(true);
    setSelectedLog(filename);
    try {
      const response = await apiClient.get<LogContent>(`/admin/logs/${filename}`);
      setLogContent(response.data.content);
    } catch (error) {
      console.error('Failed to fetch log content', error);
      setLogContent('Failed to load log content.');
    } finally {
      setIsLoadingContent(false);
    }
  };

  useEffect(() => {
    fetchLogs();
  }, [fetchLogs]);

  return (
    <ProtectedRoute>
      <div className="container mx-auto py-8 px-4 h-[calc(100vh-64px)] flex flex-col">
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-3xl font-bold">System Logs</h1>
          <Button onClick={fetchLogs} variant="outline" size="sm">
            <RefreshCw className={`w-4 h-4 mr-2 ${isLoadingList ? 'animate-spin' : ''}`} />
            Refresh List
          </Button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 flex-1 min-h-0">
          {/* Log List */}
          <Card className="md:col-span-1 flex flex-col h-full">
            <CardHeader className="py-4 border-b">
              <CardTitle className="text-sm font-medium text-muted-foreground uppercase">
                Available Logs
              </CardTitle>
            </CardHeader>
            <ScrollArea className="flex-1">
              <div className="p-2 space-y-1">
                {logs.map((log) => (
                  <button
                    key={log.name}
                    onClick={() => fetchLogContent(log.name)}
                    className={`w-full text-left p-3 rounded-lg text-sm transition-colors flex flex-col gap-1 ${
                      selectedLog === log.name
                        ? 'bg-primary text-primary-foreground'
                        : 'hover:bg-muted'
                    }`}
                  >
                    <div className="font-medium truncate flex items-center gap-2">
                        <FileText className="w-4 h-4" />
                        {log.name}
                    </div>
                    <div className="flex justify-between text-xs opacity-70">
                      <span>{formatBytes(log.size)}</span>
                      <span>{new Date(log.updated_at).toLocaleDateString()}</span>
                    </div>
                  </button>
                ))}
                {logs.length === 0 && !isLoadingList && (
                    <div className="text-center p-4 text-muted-foreground text-sm">
                        No logs found.
                    </div>
                )}
              </div>
            </ScrollArea>
          </Card>

          {/* Log Viewer */}
          <Card className="md:col-span-3 flex flex-col h-full bg-slate-950 border-slate-800">
            <CardHeader className="py-4 border-b border-slate-800 flex flex-row justify-between items-center bg-slate-900 rounded-t-xl">
              <div className="flex items-center gap-2">
                <FileText className="w-5 h-5 text-slate-400" />
                <span className="font-mono text-slate-200">
                  {selectedLog || 'Select a log file'}
                </span>
              </div>
              {selectedLog && (
                <div className="flex gap-2">
                    <Button
                        size="sm"
                        variant="ghost"
                        className="text-slate-400 hover:text-white"
                        onClick={() => fetchLogContent(selectedLog)}
                    >
                        <RefreshCw className={`w-4 h-4 ${isLoadingContent ? 'animate-spin' : ''}`} />
                    </Button>
                </div>
              )}
            </CardHeader>
            <ScrollArea className="flex-1 bg-slate-950 p-4 font-mono text-xs text-slate-300">
                {isLoadingContent ? (
                    <div className="flex items-center justify-center h-full text-slate-500">
                        <Loader2 className="w-8 h-8 animate-spin mr-2" />
                        Loading content...
                    </div>
                ) : logContent ? (
                    <pre className="whitespace-pre-wrap break-all">
                        {logContent}
                    </pre>
                ) : (
                    <div className="flex items-center justify-center h-full text-slate-600">
                        Select a log file to view its contents
                    </div>
                )}
            </ScrollArea>
          </Card>
        </div>
      </div>
    </ProtectedRoute>
  );
}
