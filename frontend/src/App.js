import React, { useState, useEffect } from 'react';

// API configuration
const API_BASE_URL = 'http://localhost:8000/api/v1';

// WebSocket configuration for real-time updates
const WS_HOST = 'localhost';
const WS_PORT = 8080;
const WS_URL = `ws://${WS_HOST}:${WS_PORT}/app/local?protocol=7&client=js&version=8.4.0-rc2&flash=false`;

/**
 * Custom fetch wrapper with error handling and JSON parsing
 */
const apiRequest = async (url, options = {}) => {
  const fullUrl = `${API_BASE_URL}${url}`;
  
  const defaultOptions = {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    ...options,
  };

  try {
    const response = await fetch(fullUrl, defaultOptions);
    const data = await response.json();
    
    if (!response.ok) {
      throw {
        response: {
          status: response.status,
          data: data
        }
      };
    }
    
    return { data };
  } catch (error) {
    throw error;
  }
};

/**
 * Simple WebSocket client for real-time updates
 */
class SimpleWebSocket {
  constructor(url) {
    this.url = url;
    this.ws = null;
    this.channels = new Map();
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 5;
    this.reconnectDelay = 3000;
    this.connect();
  }

  connect() {
    try {
      this.ws = new WebSocket(this.url);
      
      this.ws.onopen = () => {
        console.log('WebSocket connected');
        this.reconnectAttempts = 0;
        
        // Subscribe to issues channel
        this.send({
          event: 'pusher:subscribe',
          data: { channel: 'issues' }
        });
      };

      this.ws.onmessage = (event) => {
        try {
          const message = JSON.parse(event.data);
          this.handleMessage(message);
        } catch (err) {
          console.error('Error parsing WebSocket message:', err);
        }
      };

      this.ws.onclose = () => {
        console.log('WebSocket disconnected');
        this.handleReconnect();
      };

      this.ws.onerror = (error) => {
        console.error('WebSocket error:', error);
      };
      
    } catch (error) {
      console.error('Failed to create WebSocket connection:', error);
      this.handleReconnect();
    }
  }

  handleMessage(message) {
    // Handle different message types
    switch (message.event) {
      case 'pusher:connection_established':
        console.log('WebSocket connection established');
        break;
      case 'pusher_internal:subscription_succeeded':
        console.log('Subscribed to channel:', message.channel);
        break;
      case 'issue.created':
      case 'issue.updated':
        // Forward to channel listeners
        const channelListeners = this.channels.get('issues');
        if (channelListeners) {
          channelListeners.forEach(callback => {
            try {
              callback(message.event, message.data || JSON.parse(message.data || '{}'));
            } catch (err) {
              console.error('Error in channel callback:', err);
            }
          });
        }
        break;
      default:
        console.log('Unhandled WebSocket message:', message);
    }
  }

  handleReconnect() {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
      
      setTimeout(() => {
        this.connect();
      }, this.reconnectDelay);
    } else {
      console.error('Max reconnection attempts reached');
    }
  }

  send(data) {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(data));
    }
  }

  subscribe(channel) {
    if (!this.channels.has(channel)) {
      this.channels.set(channel, new Set());
    }
    
    return {
      bind: (event, callback) => {
        const listeners = this.channels.get(channel);
        listeners.add(callback);
        
        return () => {
          listeners.delete(callback);
        };
      },
      unbind_all: () => {
        this.channels.delete(channel);
      }
    };
  }

  disconnect() {
    if (this.ws) {
      this.ws.close();
    }
  }
}

/**
 * Main Issues Board Application
 * 
 * Features:
 * - Display paginated list of issues
 * - Create new issues with validation
 * - Update issue status and priority
 * - Real-time updates via WebSocket
 * - Filtering by status and priority
 * - Search functionality
 */
function App() {
  // State management for issues and UI
  const [issues, setIssues] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalIssues, setTotalIssues] = useState(0);
  
  // Filter and search state
  const [statusFilter, setStatusFilter] = useState('');
  const [priorityFilter, setPriorityFilter] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  
  // Form state for creating new issues
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [newIssue, setNewIssue] = useState({
    title: '',
    description: '',
    status: 'open',
    priority: 'medium'
  });
  const [formErrors, setFormErrors] = useState({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  // Real-time update notifications
  const [notifications, setNotifications] = useState([]);
  
  // WebSocket instance
  const [webSocket, setWebSocket] = useState(null);

  // Constants for select options
  const STATUSES = ['open', 'in_progress', 'closed'];
  const PRIORITIES = ['low', 'medium', 'high', 'critical'];

  /**
   * Load issues from API with current filters and pagination
   */
  const loadIssues = async (page = 1) => {
    try {
      setLoading(true);
      setError(null);

      // Build query parameters
      const params = new URLSearchParams();
      params.append('page', page);
      if (statusFilter) params.append('status', statusFilter);
      if (priorityFilter) params.append('priority', priorityFilter);
      if (searchTerm.trim()) params.append('search', searchTerm.trim());

      console.log('Loading issues with params:', params.toString());

      const response = await apiRequest(`/issues?${params.toString()}`);
      
      setIssues(response.data.data);
      setCurrentPage(response.data.meta.current_page);
      setTotalPages(response.data.meta.last_page);
      setTotalIssues(response.data.meta.total);

      console.log('Issues loaded successfully:', response.data.meta);
      
    } catch (err) {
      console.error('Error loading issues:', err);
      setError('Failed to load issues. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  /**
   * Create a new issue
   */
  const createIssue = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setFormErrors({});

    try {
      console.log('Creating new issue:', newIssue);

      const response = await apiRequest('/issues', {
        method: 'POST',
        body: JSON.stringify(newIssue)
      });
      
      console.log('Issue created successfully:', response.data);

      // Reset form and close modal
      setNewIssue({
        title: '',
        description: '',
        status: 'open',
        priority: 'medium'
      });
      setShowCreateForm(false);

      // Show success notification
      addNotification('Issue created successfully!', 'success');

      // Refresh issues list if we're on page 1
      if (currentPage === 1) {
        loadIssues(1);
      }

    } catch (err) {
      console.error('Error creating issue:', err);
      
      if (err.response?.status === 422) {
        // Validation errors
        setFormErrors(err.response.data.errors || {});
      } else {
        addNotification('Failed to create issue. Please try again.', 'error');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  /**
   * Update an issue (status or priority)
   */
  const updateIssue = async (issueId, updates) => {
    try {
      console.log('Updating issue:', issueId, updates);

      const response = await apiRequest(`/issues/${issueId}`, {
        method: 'PATCH',
        body: JSON.stringify(updates)
      });
      
      console.log('Issue updated successfully:', response.data);

      // Update local state immediately for better UX
      setIssues(prevIssues => 
        prevIssues.map(issue => 
          issue.id === issueId 
            ? { ...issue, ...updates, updated_at: new Date().toISOString() }
            : issue
        )
      );

      addNotification('Issue updated successfully!', 'success');

    } catch (err) {
      console.error('Error updating issue:', err);
      addNotification('Failed to update issue. Please try again.', 'error');
      
      // Reload issues to revert optimistic update
      loadIssues(currentPage);
    }
  };

  /**
   * Add a notification message
   */
  const addNotification = (message, type) => {
    const notification = {
      id: Date.now(),
      message,
      type,
      timestamp: new Date()
    };

    setNotifications(prev => [...prev, notification]);

    // Auto-remove notification after 5 seconds
    setTimeout(() => {
      setNotifications(prev => prev.filter(n => n.id !== notification.id));
    }, 5000);
  };

  /**
   * Remove a specific notification
   */
  const removeNotification = (id) => {
    setNotifications(prev => prev.filter(n => n.id !== id));
  };

  /**
   * Handle real-time updates from WebSocket
   */
  useEffect(() => {
    console.log('Setting up WebSocket connection...');

    const ws = new SimpleWebSocket(WS_URL);
    setWebSocket(ws);

    const channel = ws.subscribe('issues');

    // Handle new issue created
    const unsubscribeCreated = channel.bind('issue.created', (data) => {
      console.log('Real-time: Issue created', data);
      
      // Only add to list if we're on page 1 and filters match
      if (currentPage === 1 && shouldShowIssue(data.issue)) {
        setIssues(prevIssues => [data.issue, ...prevIssues]);
        setTotalIssues(prev => prev + 1);
        addNotification(`New issue created: ${data.issue.title}`, 'info');
      } else {
        // Just show notification
        addNotification(`New issue created: ${data.issue.title}`, 'info');
      }
    });

    // Handle issue updated
    const unsubscribeUpdated = channel.bind('issue.updated', (data) => {
      console.log('Real-time: Issue updated', data);
      
      setIssues(prevIssues => 
        prevIssues.map(issue => 
          issue.id === data.issue.id 
            ? { ...data.issue, _updated: true } // Flag for visual highlight
            : issue
        )
      );

      addNotification(`Issue updated: ${data.issue.title}`, 'info');

      // Remove highlight after animation
      setTimeout(() => {
        setIssues(prevIssues => 
          prevIssues.map(issue => 
            issue.id === data.issue.id 
              ? { ...issue, _updated: false }
              : issue
          )
        );
      }, 3000);
    });

    // Cleanup on unmount
    return () => {
      console.log('Cleaning up WebSocket connection...');
      unsubscribeCreated();
      unsubscribeUpdated();
      channel.unbind_all();
      if (ws) {
        ws.disconnect();
      }
    };
  }, [currentPage, statusFilter, priorityFilter]);

  /**
   * Check if an issue should be shown based on current filters
   */
  const shouldShowIssue = (issue) => {
    if (statusFilter && issue.status !== statusFilter) return false;
    if (priorityFilter && issue.priority !== priorityFilter) return false;
    if (searchTerm.trim()) {
      const term = searchTerm.toLowerCase();
      return issue.title.toLowerCase().includes(term) || 
             issue.description.toLowerCase().includes(term);
    }
    return true;
  };

  /**
   * Handle filter changes - reload issues when filters change
   */
  useEffect(() => {
    loadIssues(1); // Reset to page 1 when filters change
  }, [statusFilter, priorityFilter, searchTerm]);

  /**
   * Initial load
   */
  useEffect(() => {
    loadIssues(1);
  }, []);

  /**
   * Format date for display
   */
  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
  };

  /**
   * Get color class for status
   */
  const getStatusColor = (status) => {
    switch (status) {
      case 'open': return 'text-red-600 bg-red-100';
      case 'in_progress': return 'text-yellow-600 bg-yellow-100';
      case 'closed': return 'text-green-600 bg-green-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  /**
   * Get color class for priority
   */
  const getPriorityColor = (priority) => {
    switch (priority) {
      case 'low': return 'text-gray-600 bg-gray-100';
      case 'medium': return 'text-blue-600 bg-blue-100';
      case 'high': return 'text-orange-600 bg-orange-100';
      case 'critical': return 'text-red-600 bg-red-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Issues Board</h1>
              <p className="text-sm text-gray-500">
                {totalIssues} total issues
              </p>
            </div>
            <button
              onClick={() => setShowCreateForm(true)}
              className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
            >
              Create Issue
            </button>
          </div>
        </div>
      </header>

      {/* Notifications */}
      <div className="fixed top-4 right-4 z-50 space-y-2">
        {notifications.map(notification => (
          <div
            key={notification.id}
            className={`p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 ${
              notification.type === 'success' ? 'bg-green-100 text-green-800' :
              notification.type === 'error' ? 'bg-red-100 text-red-800' :
              'bg-blue-100 text-blue-800'
            }`}
          >
            <div className="flex justify-between items-start">
              <p className="text-sm font-medium">{notification.message}</p>
              <button
                onClick={() => removeNotification(notification.id)}
                className="ml-2 text-gray-400 hover:text-gray-600"
              >
                ×
              </button>
            </div>
            <p className="text-xs mt-1 opacity-75">
              {notification.timestamp.toLocaleTimeString()}
            </p>
          </div>
        ))}
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Filters */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
          <h2 className="text-lg font-medium text-gray-900 mb-4">Filters</h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {/* Status Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Status
              </label>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Statuses</option>
                {STATUSES.map(status => (
                  <option key={status} value={status}>
                    {status.replace('_', ' ').toUpperCase()}
                  </option>
                ))}
              </select>
            </div>

            {/* Priority Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Priority
              </label>
              <select
                value={priorityFilter}
                onChange={(e) => setPriorityFilter(e.target.value)}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Priorities</option>
                {PRIORITIES.map(priority => (
                  <option key={priority} value={priority}>
                    {priority.toUpperCase()}
                  </option>
                ))}
              </select>
            </div>

            {/* Search */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Search
              </label>
              <input
                type="text"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Search title or description..."
                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          {/* Clear Filters */}
          <div className="mt-4">
            <button
              onClick={() => {
                setStatusFilter('');
                setPriorityFilter('');
                setSearchTerm('');
              }}
              className="text-sm text-blue-600 hover:text-blue-800"
            >
              Clear all filters
            </button>
          </div>
        </div>

        {/* Loading State */}
        {loading && (
          <div className="text-center py-8">
            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p className="mt-2 text-gray-600">Loading issues...</p>
          </div>
        )}

        {/* Error State */}
        {error && (
          <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            {error}
          </div>
        )}

        {/* Issues List */}
        {!loading && !error && (
          <>
            <div className="space-y-4">
              {issues.map(issue => (
                <div
                  key={issue.id}
                  className={`bg-white rounded-lg shadow-sm border p-6 transition-all duration-300 ${
                    issue._updated ? 'ring-2 ring-blue-500 bg-blue-50' : ''
                  }`}
                >
                  <div className="flex justify-between items-start mb-4">
                    <div className="flex-1">
                      <h3 className="text-lg font-semibold text-gray-900 mb-2">
                        {issue.title}
                      </h3>
                      <p className="text-gray-600 mb-4">
                        {issue.description}
                      </p>
                    </div>
                    <div className="flex space-x-2 ml-4">
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(issue.status)}`}>
                        {issue.status.replace('_', ' ').toUpperCase()}
                      </span>
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${getPriorityColor(issue.priority)}`}>
                        {issue.priority.toUpperCase()}
                      </span>
                    </div>
                  </div>

                  <div className="flex justify-between items-center">
                    <div className="text-sm text-gray-500">
                      <p>Created: {formatDate(issue.created_at)}</p>
                      <p>Updated: {formatDate(issue.updated_at)}</p>
                    </div>

                    {/* Quick Actions */}
                    <div className="flex space-x-2">
                      {/* Status Update */}
                      <select
                        value={issue.status}
                        onChange={(e) => updateIssue(issue.id, { status: e.target.value })}
                        className="text-sm border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        {STATUSES.map(status => (
                          <option key={status} value={status}>
                            {status.replace('_', ' ').toUpperCase()}
                          </option>
                        ))}
                      </select>

                      {/* Priority Update */}
                      <select
                        value={issue.priority}
                        onChange={(e) => updateIssue(issue.id, { priority: e.target.value })}
                        className="text-sm border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        {PRIORITIES.map(priority => (
                          <option key={priority} value={priority}>
                            {priority.toUpperCase()}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {/* Empty State */}
            {issues.length === 0 && (
              <div className="text-center py-12">
                <div className="text-gray-400 mb-4">
                  <svg className="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <h3 className="text-lg font-medium text-gray-900 mb-2">No issues found</h3>
                <p className="text-gray-600 mb-4">
                  {statusFilter || priorityFilter || searchTerm
                    ? 'Try adjusting your filters or search terms.'
                    : 'Get started by creating your first issue.'
                  }
                </p>
                <button
                  onClick={() => setShowCreateForm(true)}
                  className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                >
                  Create First Issue
                </button>
              </div>
            )}

            {/* Pagination */}
            {totalPages > 1 && (
              <div className="flex justify-center items-center space-x-4 mt-8">
                <button
                  onClick={() => loadIssues(currentPage - 1)}
                  disabled={currentPage === 1}
                  className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Previous
                </button>

                <span className="text-sm text-gray-700">
                  Page {currentPage} of {totalPages}
                </span>

                <button
                  onClick={() => loadIssues(currentPage + 1)}
                  disabled={currentPage === totalPages}
                  className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Next
                </button>
              </div>
            )}
          </>
        )}
      </div>

      {/* Create Issue Modal */}
      {showCreateForm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-40">
          <div className="bg-white rounded-lg max-w-md w-full p-6">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-xl font-bold text-gray-900">Create New Issue</h2>
              <button
                onClick={() => {
                  setShowCreateForm(false);
                  setFormErrors({});
                }}
                className="text-gray-400 hover:text-gray-600"
              >
                ×
              </button>
            </div>

            <form onSubmit={createIssue} className="space-y-4">
              {/* Title */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Title *
                </label>
                <input
                  type="text"
                  value={newIssue.title}
                  onChange={(e) => setNewIssue(prev => ({ ...prev, title: e.target.value }))}
                  className={`w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                    formErrors.title ? 'border-red-500' : 'border-gray-300'
                  }`}
                  placeholder="Enter issue title..."
                />
                {formErrors.title && (
                  <p className="text-red-600 text-sm mt-1">{formErrors.title[0]}</p>
                )}
              </div>

              {/* Description */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Description *
                </label>
                <textarea
                  value={newIssue.description}
                  onChange={(e) => setNewIssue(prev => ({ ...prev, description: e.target.value }))}
                  rows={4}
                  className={`w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                    formErrors.description ? 'border-red-500' : 'border-gray-300'
                  }`}
                  placeholder="Describe the issue in detail..."
                />
                {formErrors.description && (
                  <p className="text-red-600 text-sm mt-1">{formErrors.description[0]}</p>
                )}
              </div>

              {/* Status */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Status *
                </label>
                <select
                  value={newIssue.status}
                  onChange={(e) => setNewIssue(prev => ({ ...prev, status: e.target.value }))}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  {STATUSES.map(status => (
                    <option key={status} value={status}>
                      {status.replace('_', ' ').toUpperCase()}
                    </option>
                  ))}
                </select>
              </div>

              {/* Priority */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Priority *
                </label>
                <select
                  value={newIssue.priority}
                  onChange={(e) => setNewIssue(prev => ({ ...prev, priority: e.target.value }))}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  {PRIORITIES.map(priority => (
                    <option key={priority} value={priority}>
                      {priority.toUpperCase()}
                    </option>
                  ))}
                </select>
              </div>

              {/* Form Actions */}
              <div className="flex space-x-3 pt-4">
                <button
                  type="button"
                  onClick={() => {
                    setShowCreateForm(false);
                    setFormErrors({});
                  }}
                  className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {isSubmitting ? 'Creating...' : 'Create Issue'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

export default App;