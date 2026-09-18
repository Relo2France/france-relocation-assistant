import React from 'react';
import { MutationCache, QueryClient, QueryClientProvider } from '@tanstack/react-query';
import ReactDOM from 'react-dom/client';
import App from './App';
import { notify } from './components/shared/Toaster';
import './index.css';

// Create a client
const queryClient = new QueryClient({
  // A save that fails says so, unless the screen that made it shows the
  // error inline (meta.silentError).
  mutationCache: new MutationCache({
    onError: (error, _vars, _ctx, mutation) => {
      if (mutation.meta?.silentError) return;
      const message = error instanceof Error && error.message ? error.message : 'Please try again.';
      notify(`That did not save. ${message}`);
    },
  }),
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

// Mount the app
const rootElement = document.getElementById('fra-portal-root');

if (rootElement) {
  ReactDOM.createRoot(rootElement).render(
    <React.StrictMode>
      <QueryClientProvider client={queryClient}>
        <App />
      </QueryClientProvider>
    </React.StrictMode>
  );
}
