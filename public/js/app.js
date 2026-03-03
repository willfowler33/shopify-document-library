/**
 * TCP Document Library - React Frontend
 */

(function() {
    'use strict';

    const { useState, useEffect, useMemo } = React;
    const e = React.createElement;

    // Main App Component
    function DocumentLibraryApp({ initialFilters = {} }) {
        const [documents, setDocuments] = useState([]);
        const [filters, setFilters] = useState({
            categories: [],
            brands: [],
            productSystems: [],
            types: []
        });
        const [loading, setLoading] = useState(true);
        const [error, setError] = useState(null);
        const [pagination, setPagination] = useState({
            page: 1,
            per_page: 30,
            total_pages: 1,
            total_items: 0
        });

        // Filter state
        const [searchTerm, setSearchTerm] = useState(initialFilters.search || '');
        const [selectedCategory, setSelectedCategory] = useState(initialFilters.category || '');
        const [selectedBrand, setSelectedBrand] = useState(initialFilters.brand || '');
        const [selectedProductSystem, setSelectedProductSystem] = useState(initialFilters.product_system || '');
        const [selectedType, setSelectedType] = useState(initialFilters.document_type || '');
        const [currentPage, setCurrentPage] = useState(1);

        // Fetch filter options
        useEffect(() => {
            fetchFilters();
        }, []);

        // Fetch documents when filters change or page changes
        useEffect(() => {
            fetchDocuments();
        }, [searchTerm, selectedCategory, selectedBrand, selectedProductSystem, selectedType, currentPage]);

        const fetchFilters = async () => {
            try {
                const response = await fetch(`${tcpDocsData.restUrl}/filters`, {
                    headers: {
                        'X-WP-Nonce': tcpDocsData.nonce
                    }
                });

                const result = await response.json();

                if (result.success) {
                    setFilters({
                        categories: result.data.categories || [],
                        brands: result.data.brands || [],
                        productSystems: result.data.product_systems || [],
                        types: result.data.document_types || []
                    });
                }
            } catch (err) {
                console.error('Error fetching filters:', err);
            }
        };

        const fetchDocuments = async () => {
            setLoading(true);
            setError(null);

            try {
                const params = new URLSearchParams();
                if (searchTerm) params.append('search', searchTerm);
                if (selectedCategory) params.append('category', selectedCategory);
                if (selectedBrand) params.append('brand', selectedBrand);
                if (selectedProductSystem) params.append('product_system', selectedProductSystem);
                if (selectedType) params.append('document_type', selectedType);
                params.append('page', currentPage);
                params.append('per_page', 30);

                const response = await fetch(`${tcpDocsData.restUrl}/documents?${params}`, {
                    headers: {
                        'X-WP-Nonce': tcpDocsData.nonce
                    }
                });

                const result = await response.json();

                if (result.success) {
                    setDocuments(result.data || []);
                    if (result.pagination) {
                        setPagination(result.pagination);
                    }
                } else {
                    setError(result.message || 'Failed to load documents');
                }
            } catch (err) {
                console.error('Error fetching documents:', err);
                setError('Failed to load documents. Please try again.');
            } finally {
                setLoading(false);
            }
        };

        const clearFilters = () => {
            setSearchTerm('');
            setSelectedCategory('');
            setSelectedBrand('');
            setSelectedProductSystem('');
            setSelectedType('');
            setCurrentPage(1);
        };

        const hasActiveFilters = searchTerm || selectedCategory || selectedBrand || selectedProductSystem || selectedType;

        // Generate page numbers for pagination
        const getPageNumbers = () => {
            const pages = [];
            const totalPages = pagination.total_pages;
            const current = currentPage;

            // Always show first page
            pages.push(1);

            // Show pages around current page
            let start = Math.max(2, current - 2);
            let end = Math.min(totalPages - 1, current + 2);

            // Add ellipsis if needed
            if (start > 2) {
                pages.push('...');
            }

            // Add middle pages
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            // Add ellipsis if needed
            if (end < totalPages - 1) {
                pages.push('...');
            }

            // Always show last page if there's more than 1 page
            if (totalPages > 1) {
                pages.push(totalPages);
            }

            return pages;
        };

        return e('div', { className: 'tcp-app' },
            // Search and Filters
            e('div', { className: 'tcp-search-filters' },
                e('div', { className: 'tcp-search-box' },
                    e('input', {
                        type: 'text',
                        className: 'tcp-search-input',
                        placeholder: tcpDocsData.strings.searchPlaceholder,
                        value: searchTerm,
                        onChange: (ev) => setSearchTerm(ev.target.value)
                    })
                ),
                e('div', { className: 'tcp-filters' },
                    e('select', {
                        className: 'tcp-filter-select',
                        value: selectedCategory,
                        onChange: (ev) => setSelectedCategory(ev.target.value)
                    },
                        e('option', { value: '' }, tcpDocsData.strings.allCategories),
                        filters.categories.map(cat =>
                            e('option', { key: cat, value: cat }, cat)
                        )
                    ),
                    e('select', {
                        className: 'tcp-filter-select',
                        value: selectedBrand,
                        onChange: (ev) => setSelectedBrand(ev.target.value)
                    },
                        e('option', { value: '' }, tcpDocsData.strings.allBrands),
                        filters.brands.map(brand =>
                            e('option', { key: brand, value: brand }, brand)
                        )
                    ),
                    e('select', {
                        className: 'tcp-filter-select',
                        value: selectedProductSystem,
                        onChange: (ev) => setSelectedProductSystem(ev.target.value)
                    },
                        e('option', { value: '' }, 'All Product Systems'),
                        filters.productSystems.map(ps =>
                            e('option', { key: ps, value: ps }, ps)
                        )
                    ),
                    e('select', {
                        className: 'tcp-filter-select',
                        value: selectedType,
                        onChange: (ev) => setSelectedType(ev.target.value)
                    },
                        e('option', { value: '' }, tcpDocsData.strings.allTypes),
                        filters.types.map(type =>
                            e('option', { key: type, value: type }, type)
                        )
                    ),
                    hasActiveFilters && e('button', {
                        className: 'tcp-clear-filters',
                        onClick: clearFilters
                    }, 'Clear Filters')
                )
            ),

            // Results info
            !loading && !error && e('div', { className: 'tcp-results-info' },
                e('span', null, `${pagination.total_items} document${pagination.total_items !== 1 ? 's' : ''} found`),
                hasActiveFilters && e('span', null, '(filtered)'),
                pagination.total_pages > 1 && e('span', null, ` - Page ${pagination.page} of ${pagination.total_pages}`)
            ),

            // Loading state
            loading && e('div', { className: 'tcp-loading' },
                e('div', { className: 'tcp-spinner' }),
                e('p', null, tcpDocsData.strings.loading)
            ),

            // Error state
            error && e('div', { className: 'tcp-error-state' },
                e('p', null, error)
            ),

            // Documents grid
            !loading && !error && documents.length > 0 && e('div', { className: 'tcp-documents-grid' },
                documents.map(doc => e(DocumentCard, { key: doc.id, document: doc }))
            ),

            // Pagination
            !loading && !error && pagination.total_pages > 1 && e('div', { className: 'tcp-pagination' },
                e('button', {
                    className: 'tcp-page-button',
                    disabled: currentPage === 1,
                    onClick: () => setCurrentPage(currentPage - 1)
                }, '← Previous'),

                e('div', { className: 'tcp-page-numbers' },
                    getPageNumbers().map((page, index) => {
                        if (page === '...') {
                            return e('span', { key: `ellipsis-${index}`, className: 'tcp-page-ellipsis' }, '...');
                        }
                        return e('button', {
                            key: page,
                            className: `tcp-page-number ${page === currentPage ? 'active' : ''}`,
                            onClick: () => setCurrentPage(page)
                        }, page);
                    })
                ),

                e('button', {
                    className: 'tcp-page-button',
                    disabled: currentPage === pagination.total_pages,
                    onClick: () => setCurrentPage(currentPage + 1)
                }, 'Next →')
            ),

            // Empty state
            !loading && !error && documents.length === 0 && e('div', { className: 'tcp-empty-state' },
                e('div', { className: 'tcp-empty-icon' }, '📄'),
                e('p', { className: 'tcp-empty-message' }, tcpDocsData.strings.noResults)
            )
        );
    }

    // Document Card Component
    function DocumentCard({ document: doc }) {
        return e('div', { className: 'tcp-document-card' },
            e('h3', { className: 'tcp-document-title' }, doc.title),

            doc.description && e('p', { className: 'tcp-document-description' }, doc.description),

            e('div', { className: 'tcp-document-meta' },
                doc.category && e('span', { className: 'tcp-meta-badge category' }, doc.category),
                doc.brand && e('span', { className: 'tcp-meta-badge brand' }, doc.brand),
                doc.product_system && e('span', { className: 'tcp-meta-badge product-system' }, doc.product_system),
                doc.document_type && e('span', { className: 'tcp-meta-badge type' }, doc.document_type)
            ),

            doc.hubspot_file_url && e('a', {
                href: doc.hubspot_file_url,
                className: 'tcp-view-button',
                target: '_blank',
                rel: 'noopener noreferrer'
            }, tcpDocsData.strings.viewPdf)
        );
    }

    // Initialize app on DOM ready
    function initApp() {
        const container = document.getElementById('tcp-document-library');

        if (!container) {
            return;
        }

        // Get initial filters from data attributes
        const initialFilters = {
            search: container.dataset.search || '',
            category: container.dataset.category || '',
            brand: container.dataset.brand || '',
            product_system: container.dataset.product_system || '',
            document_type: container.dataset.document_type || ''
        };

        // Render the app
        const root = ReactDOM.createRoot(container);
        root.render(e(DocumentLibraryApp, { initialFilters }));
    }

    // Wait for DOM and React to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initApp);
    } else {
        initApp();
    }
})();
