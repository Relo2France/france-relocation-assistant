import { useState, useRef, useEffect } from 'react';
import { clsx } from 'clsx';
import { Square, CheckSquare, Plus, X, Loader2 } from 'lucide-react';
import {
  useTaskChecklist,
  useUpdateTaskChecklistItem,
  useAddTaskChecklistItem,
  useDeleteTaskChecklistItem,
} from '@/hooks/useApi';

interface TaskChecklistProps {
  taskId: number;
  editable?: boolean;
}

export default function TaskChecklist({ taskId, editable = true }: TaskChecklistProps) {
  const [newItemTitle, setNewItemTitle] = useState('');
  const [isAddingItem, setIsAddingItem] = useState(false);
  const [hoveredItemId, setHoveredItemId] = useState<string | null>(null);
  const newItemInputRef = useRef<HTMLInputElement>(null);

  const { data: checklist, isLoading } = useTaskChecklist(taskId);

  // Focus input when adding new item
  useEffect(() => {
    if (isAddingItem && newItemInputRef.current) {
      newItemInputRef.current.focus();
    }
  }, [isAddingItem]);
  const updateItem = useUpdateTaskChecklistItem(taskId);
  const addItem = useAddTaskChecklistItem(taskId);
  const deleteItem = useDeleteTaskChecklistItem(taskId);

  const items = checklist?.items || [];
  const completedCount = items.filter((item) => item.completed).length;
  const totalCount = items.length;

  const handleToggleItem = (itemId: string, completed: boolean) => {
    if (!editable) return;
    updateItem.mutate({ itemId, completed: !completed });
  };

  const handleAddItem = async () => {
    if (!newItemTitle.trim() || !editable) return;

    try {
      await addItem.mutateAsync(newItemTitle.trim());
      setNewItemTitle('');
      setIsAddingItem(false);
    } catch (error) {
      console.error('Failed to add checklist item:', error);
    }
  };

  const handleDeleteItem = (itemId: string) => {
    if (!editable) return;
    deleteItem.mutate(itemId);
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      handleAddItem();
    } else if (e.key === 'Escape') {
      setNewItemTitle('');
      setIsAddingItem(false);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-8">
        <Loader2 className="w-5 h-5 animate-spin text-gray-400" />
      </div>
    );
  }

  return (
    <div className="space-y-3">
      {/* Header */}
      <div className="flex items-center justify-between">
        <h4 className="text-sm font-medium text-gray-700">
          Task Checklist
          {totalCount > 0 && (
            <span className="ml-2 text-xs text-gray-500">
              ({completedCount}/{totalCount})
            </span>
          )}
        </h4>
        {totalCount > 0 && (
          <div className="flex-1 max-w-xs mx-3">
            <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
              <div
                className="h-full bg-primary-500 transition-all duration-300"
                style={{ width: `${totalCount > 0 ? (completedCount / totalCount) * 100 : 0}%` }}
              />
            </div>
          </div>
        )}
      </div>

      {/* Items list */}
      <div className="space-y-1.5">
        {items.length === 0 && !isAddingItem ? (
          <p className="text-sm text-gray-400 italic py-2">No checklist items</p>
        ) : (
          items.map((item) => (
            <div
              key={item.id}
              className="group flex items-start gap-2 p-2 rounded-lg hover:bg-gray-50 transition-colors"
              onMouseEnter={() => setHoveredItemId(item.id)}
              onMouseLeave={() => setHoveredItemId(null)}
            >
              <button
                onClick={() => handleToggleItem(item.id, item.completed)}
                disabled={!editable || updateItem.isPending}
                className={clsx(
                  'flex-shrink-0 mt-0.5 transition-colors',
                  !editable && 'cursor-default'
                )}
              >
                {item.completed ? (
                  <CheckSquare className="w-4 h-4 text-primary-600" />
                ) : (
                  <Square className="w-4 h-4 text-gray-400 hover:text-gray-600" />
                )}
              </button>

              <span
                className={clsx(
                  'flex-1 text-sm transition-all',
                  item.completed
                    ? 'text-gray-400 line-through'
                    : 'text-gray-700'
                )}
              >
                {item.title}
              </span>

              {editable && hoveredItemId === item.id && (
                <button
                  onClick={() => handleDeleteItem(item.id)}
                  disabled={deleteItem.isPending}
                  className="flex-shrink-0 p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                  title="Delete item"
                >
                  <X className="w-3.5 h-3.5" />
                </button>
              )}
            </div>
          ))
        )}

        {/* Add item input */}
        {isAddingItem ? (
          <div className="flex items-center gap-2 p-2">
            <Square className="w-4 h-4 text-gray-300 flex-shrink-0 mt-0.5" />
            <input
              ref={newItemInputRef}
              type="text"
              value={newItemTitle}
              onChange={(e) => setNewItemTitle(e.target.value)}
              onKeyDown={handleKeyDown}
              onBlur={() => {
                if (!newItemTitle.trim()) {
                  setIsAddingItem(false);
                }
              }}
              placeholder="Add item..."
              className="flex-1 text-sm bg-transparent border-none focus:outline-none focus:ring-0 p-0"
              disabled={addItem.isPending}
            />
            {addItem.isPending && (
              <Loader2 className="w-4 h-4 animate-spin text-gray-400" />
            )}
          </div>
        ) : (
          editable && (
            <button
              onClick={() => setIsAddingItem(true)}
              className="flex items-center gap-2 p-2 text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-lg transition-colors w-full"
            >
              <Plus className="w-4 h-4" />
              <span>Add item...</span>
            </button>
          )
        )}
      </div>
    </div>
  );
}
