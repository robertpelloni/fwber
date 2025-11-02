"use client";

import { useState } from 'react';
import { useAuth } from '@/lib/auth-context';
import ProtectedRoute from '@/components/ProtectedRoute';
import SmartContentEditor from '@/components/SmartContentEditor';
import AIProfileBuilder from '@/components/AIProfileBuilder';
import { useContentGenerationAnalytics } from '@/lib/hooks/use-content-generation';

export default function ContentGenerationPage() {
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState('profile');
  const [generatedContent, setGeneratedContent] = useState<string>('');
  
  const { generationStats, optimizationStats, isLoading, error } = useContentGenerationAnalytics();

  const tabs = [
    { id: 'profile', name: 'AI Profile Builder', icon: '👤' },
    { id: 'editor', name: 'Smart Content Editor', icon: '✍️' },
    { id: 'analytics', name: 'Analytics Dashboard', icon: '📊' },
  ];

  const handleProfileGenerated = (profile: any) => {
    setGeneratedContent(profile.content);
  };

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="text-center mb-8">
            <h1 className="text-4xl font-bold text-gray-900 mb-2">AI Content Generation</h1>
            <p className="text-gray-600">Leverage the power of AI to create engaging content</p>
          </div>

          {/* Tab Navigation */}
          <div className="mb-8">
            <nav className="flex space-x-8" aria-label="Tabs">
              {tabs.map((tab) => (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`flex items-center space-x-2 py-4 px-1 border-b-2 font-medium text-sm ${
                    activeTab === tab.id
                      ? 'border-blue-500 text-blue-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  }`}
                >
                  <span className="text-lg">{tab.icon}</span>
                  <span>{tab.name}</span>
                </button>
              ))}
            </nav>
          </div>

          {/* Tab Content */}
          <div className="bg-white rounded-lg shadow-md">
            {activeTab === 'profile' && (
              <div className="p-6">
                <AIProfileBuilder onProfileGenerated={handleProfileGenerated} />
              </div>
            )}

            {activeTab === 'editor' && (
              <div className="p-6">
                <div className="mb-6">
                  <h2 className="text-2xl font-semibold mb-2">Smart Content Editor</h2>
                  <p className="text-gray-600">
                    Create and optimize content with AI-powered suggestions and real-time analysis.
                  </p>
                </div>
                
                <SmartContentEditor
                  initialContent={generatedContent}
                  onContentChange={(content) => setGeneratedContent(content)}
                  context={{
                    location: {
                      latitude: user?.latitude || 0,
                      longitude: user?.longitude || 0,
                    },
                    interests: user?.interests || [],
                  }}
                  placeholder="Start writing your content here..."
                  maxLength={1000}
                  enableOptimization={true}
                  enableQualityAnalysis={true}
                  showSuggestions={true}
                />
              </div>
            )}

            {activeTab === 'analytics' && (
              <div className="p-6">
                <div className="mb-6">
                  <h2 className="text-2xl font-semibold mb-2">Content Generation Analytics</h2>
                  <p className="text-gray-600">
                    Track your content generation performance and optimization results.
                  </p>
                </div>

                {isLoading ? (
                  <div className="text-center py-8">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
                    <p className="mt-4 text-gray-600">Loading analytics...</p>
                  </div>
                ) : error ? (
                  <div className="bg-red-50 p-4 rounded-lg">
                    <h3 className="font-semibold text-red-800 mb-2">Error Loading Analytics</h3>
                    <p className="text-sm text-red-700">
                      {error.message || 'An error occurred while loading analytics data.'}
                    </p>
                  </div>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Generation Stats */}
                    {generationStats && (
                      <div className="bg-blue-50 p-6 rounded-lg">
                        <h3 className="text-lg font-semibold text-blue-800 mb-4">Generation Statistics</h3>
                        <div className="grid grid-cols-2 gap-4">
                          <div className="text-center">
                            <div className="text-3xl font-bold text-blue-600">
                              {generationStats.total_generations}
                            </div>
                            <div className="text-sm text-blue-700">Total Generations</div>
                          </div>
                          <div className="text-center">
                            <div className="text-3xl font-bold text-green-600">
                              {Math.round(generationStats.user_satisfaction * 100)}%
                            </div>
                            <div className="text-sm text-green-700">User Satisfaction</div>
                          </div>
                          <div className="text-center">
                            <div className="text-3xl font-bold text-purple-600">
                              {generationStats.successful_generations}
                            </div>
                            <div className="text-sm text-purple-700">Successful</div>
                          </div>
                          <div className="text-center">
                            <div className="text-3xl font-bold text-orange-600">
                              {generationStats.average_generation_time}s
                            </div>
                            <div className="text-sm text-orange-700">Avg. Time</div>
                          </div>
                        </div>
                      </div>
                    )}

                    {/* Optimization Stats */}
                    {optimizationStats && (
                      <div className="bg-green-50 p-6 rounded-lg">
                        <h3 className="text-lg font-semibold text-green-800 mb-4">Optimization Statistics</h3>
                        <div className="grid grid-cols-2 gap-4">
                          <div className="text-center">
                            <div className="text-3xl font-bold text-green-600">
                              {optimizationStats.total_optimizations}
                            </div>
                            <div className="text-sm text-green-700">Total Optimizations</div>
                          </div>
                          <div className="text-center">
                            <div className="text-3xl font-bold text-blue-600">
                              {Math.round(optimizationStats.average_improvement_score * 100)}%
                            </div>
                            <div className="text-sm text-blue-700">Avg. Improvement</div>
                          </div>
                          <div className="text-center">
                            <div className="text-3xl font-bold text-purple-600">
                              {optimizationStats.successful_optimizations}
                            </div>
                            <div className="text-sm text-purple-700">Successful</div>
                          </div>
                          <div className="text-center">
                            <div className="text-3xl font-bold text-orange-600">
                              {Object.keys(optimizationStats.optimization_types_usage).length}
                            </div>
                            <div className="text-sm text-orange-700">Types Used</div>
                          </div>
                        </div>
                      </div>
                    )}

                    {/* Popular Types */}
                    {generationStats?.most_popular_types && (
                      <div className="bg-purple-50 p-6 rounded-lg">
                        <h3 className="text-lg font-semibold text-purple-800 mb-4">Popular Content Types</h3>
                        <div className="space-y-2">
                          {generationStats.most_popular_types.map((type: string, index: number) => (
                            <div key={type} className="flex justify-between items-center">
                              <span className="text-sm text-purple-700 capitalize">{type}</span>
                              <span className="text-sm font-medium text-purple-600">#{index + 1}</span>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}

                    {/* Common Improvements */}
                    {optimizationStats?.most_common_improvements && (
                      <div className="bg-yellow-50 p-6 rounded-lg">
                        <h3 className="text-lg font-semibold text-yellow-800 mb-4">Common Improvements</h3>
                        <div className="space-y-2">
                          {optimizationStats.most_common_improvements.map((improvement: string, index: number) => (
                            <div key={improvement} className="flex justify-between items-center">
                              <span className="text-sm text-yellow-700">{improvement}</span>
                              <span className="text-sm font-medium text-yellow-600">#{index + 1}</span>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Quick Actions */}
          <div className="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="bg-white p-6 rounded-lg shadow-md">
              <h3 className="text-lg font-semibold text-gray-800 mb-2">🚀 Quick Start</h3>
              <p className="text-gray-600 mb-4">
                Get started with AI content generation in just a few clicks.
              </p>
              <button
                onClick={() => setActiveTab('profile')}
                className="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition-colors"
              >
                Create Profile
              </button>
            </div>

            <div className="bg-white p-6 rounded-lg shadow-md">
              <h3 className="text-lg font-semibold text-gray-800 mb-2">✍️ Smart Editor</h3>
              <p className="text-gray-600 mb-4">
                Use our AI-powered editor to create and optimize content.
              </p>
              <button
                onClick={() => setActiveTab('editor')}
                className="w-full bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600 transition-colors"
              >
                Start Writing
              </button>
            </div>

            <div className="bg-white p-6 rounded-lg shadow-md">
              <h3 className="text-lg font-semibold text-gray-800 mb-2">📊 Analytics</h3>
              <p className="text-gray-600 mb-4">
                Track your content performance and optimization results.
              </p>
              <button
                onClick={() => setActiveTab('analytics')}
                className="w-full bg-purple-500 text-white py-2 px-4 rounded-lg hover:bg-purple-600 transition-colors"
              >
                View Analytics
              </button>
            </div>
          </div>
        </div>
      </div>
    </ProtectedRoute>
  );
}
