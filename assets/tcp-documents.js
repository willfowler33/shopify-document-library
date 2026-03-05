/**
 * TCP Document Library - Shopify Frontend
 * Fetches document data directly from Google Sheets API.
 * No backend required — the Google Sheet is the database.
 */

(function () {
  'use strict';

  // ── Configuration (injected from Liquid via data attributes) ──
  let CONFIG = {
    apiKey: '',
    spreadsheetId: '',
    perPage: 30,
  };

  // ── Google Sheets fetching ──

  async function fetchSheetNames(apiKey, spreadsheetId) {
    const url = `https://sheets.googleapis.com/v4/spreadsheets/${spreadsheetId}?key=${apiKey}&fields=sheets.properties.title`;
    const res = await fetch(url);
    if (!res.ok) throw new Error(`Sheets API error ${res.status}`);
    const data = await res.json();
    return (data.sheets || []).map(s => s.properties.title);
  }

  async function fetchSheetData(apiKey, spreadsheetId, sheetName) {
    const range = encodeURIComponent(sheetName);
    const url = `https://sheets.googleapis.com/v4/spreadsheets/${spreadsheetId}/values/${range}?key=${apiKey}`;
    const res = await fetch(url);
    if (!res.ok) throw new Error(`Sheets API error ${res.status} for sheet "${sheetName}"`);
    const data = await res.json();
    return data.values || [];
  }

  function mapColumns(headers) {
    const map = {};
    headers.forEach((header, index) => {
      const h = header.toLowerCase().trim();
      if (h === 'title') map.title = index;
      else if (h === 'description') map.description = index;
      else if (h === 'category' || h.startsWith('category')) map.category = index;
      else if (h === 'brand') map.brand = index;
      else if (h === 'product system' || h.startsWith('product system')) map.product_system = index;
      else if (h === 'document type' || h === 'type' || h.startsWith('document type')) map.document_type = index;
      else if (h === 'hubspot file url' || h === 'file url') map.hubspot_file_url = index;
      else if (h === 'hubspot file id' || h === 'file id') map.hubspot_file_id = index;
      else if (h === 'file name' || h === 'filename') map.file_name = index;
    });
    return map;
  }

  function parseRows(rows) {
    if (!rows || rows.length < 2) return [];
    const headers = rows[0];
    const colMap = mapColumns(headers);
    const documents = [];

    for (let i = 1; i < rows.length; i++) {
      const row = rows[i];
      const doc = {
        title: '',
        description: '',
        category: '',
        brand: '',
        product_system: '',
        document_type: '',
        file_name: '',
        hubspot_file_url: '',
        hubspot_file_id: '',
      };

      for (const [field, idx] of Object.entries(colMap)) {
        if (row[idx] !== undefined) {
          doc[field] = row[idx].trim();
        }
      }

      // Skip rows without a file URL
      if (!doc.hubspot_file_url) continue;

      // Use title fallback
      if (!doc.title) doc.title = doc.file_name || 'Untitled Document';

      documents.push(doc);
    }

    return documents;
  }

  async function fetchAllDocuments(apiKey, spreadsheetId) {
    const sheetNames = await fetchSheetNames(apiKey, spreadsheetId);
    let allDocs = [];

    for (const name of sheetNames) {
      try {
        const rows = await fetchSheetData(apiKey, spreadsheetId, name);
        const docs = parseRows(rows);
        allDocs = allDocs.concat(docs);
      } catch (err) {
        console.warn(`TCP Docs: Error reading sheet "${name}":`, err);
      }
    }

    // Sort by title
    allDocs.sort((a, b) => a.title.localeCompare(b.title));
    return allDocs;
  }

  // ── Simple caching (sessionStorage) ──

  const CACHE_KEY = 'tcp_docs_cache';
  const CACHE_TTL = 5 * 60 * 1000; // 5 minutes

  function getCachedDocs() {
    try {
      const raw = sessionStorage.getItem(CACHE_KEY);
      if (!raw) return null;
      const cached = JSON.parse(raw);
      if (Date.now() - cached.timestamp > CACHE_TTL) {
        sessionStorage.removeItem(CACHE_KEY);
        return null;
      }
      return cached.documents;
    } catch {
      return null;
    }
  }

  function setCachedDocs(documents) {
    try {
      sessionStorage.setItem(CACHE_KEY, JSON.stringify({
        timestamp: Date.now(),
        documents: documents,
      }));
    } catch {
      // sessionStorage full or unavailable — ignore
    }
  }

  // ── Filtering & Pagination helpers ──

  function getUniqueValues(documents, field) {
    const values = new Set();
    documents.forEach(doc => {
      if (doc[field]) values.add(doc[field]);
    });
    return Array.from(values).sort();
  }

  function filterDocuments(documents, filters) {
    return documents.filter(doc => {
      if (filters.search) {
        const term = filters.search.toLowerCase();
        const searchable = (doc.title + ' ' + doc.description + ' ' + doc.file_name).toLowerCase();
        if (!searchable.includes(term)) return false;
      }
      if (filters.category && doc.category !== filters.category) return false;
      if (filters.brand && doc.brand !== filters.brand) return false;
      if (filters.product_system && doc.product_system !== filters.product_system) return false;
      if (filters.document_type && doc.document_type !== filters.document_type) return false;
      return true;
    });
  }

  // ── Rendering (vanilla DOM, no React dependency) ──

  function h(tag, attrs, ...children) {
    const el = document.createElement(tag);
    if (attrs) {
      for (const [k, v] of Object.entries(attrs)) {
        if (k === 'className') el.className = v;
        else if (k.startsWith('on') && typeof v === 'function') {
          el.addEventListener(k.slice(2).toLowerCase(), v);
        } else {
          el.setAttribute(k, v);
        }
      }
    }
    children.flat().forEach(child => {
      if (child == null || child === false) return;
      if (typeof child === 'string' || typeof child === 'number') {
        el.appendChild(document.createTextNode(child));
      } else {
        el.appendChild(child);
      }
    });
    return el;
  }

  function renderApp(container, state) {
    container.innerHTML = '';

    const { documents, allDocuments, filters, currentPage, perPage } = state;

    // Search & Filters bar
    const searchInput = h('input', {
      type: 'text',
      className: 'tcp-search-input',
      placeholder: 'Search documents...',
      value: filters.search || '',
      onInput: (e) => {
        state.filters.search = e.target.value;
        state.currentPage = 1;
        update();
      },
    });

    const categories = getUniqueValues(allDocuments, 'category');
    const brands = getUniqueValues(allDocuments, 'brand');
    const productSystems = getUniqueValues(allDocuments, 'product_system');
    const docTypes = getUniqueValues(allDocuments, 'document_type');

    function makeSelect(options, current, label, onChange) {
      const select = h('select', { className: 'tcp-filter-select', onChange });
      select.appendChild(h('option', { value: '' }, label));
      options.forEach(opt => {
        const o = h('option', { value: opt }, opt);
        if (opt === current) o.selected = true;
        select.appendChild(o);
      });
      return select;
    }

    const hasActive = filters.search || filters.category || filters.brand || filters.product_system || filters.document_type;

    const filtersRow = h('div', { className: 'tcp-filters' },
      makeSelect(categories, filters.category, 'All Categories', e => { state.filters.category = e.target.value; state.currentPage = 1; update(); }),
      makeSelect(brands, filters.brand, 'All Brands', e => { state.filters.brand = e.target.value; state.currentPage = 1; update(); }),
      makeSelect(productSystems, filters.product_system, 'All Product Systems', e => { state.filters.product_system = e.target.value; state.currentPage = 1; update(); }),
      makeSelect(docTypes, filters.document_type, 'All Types', e => { state.filters.document_type = e.target.value; state.currentPage = 1; update(); }),
      hasActive ? h('button', {
        className: 'tcp-clear-filters',
        onClick: () => {
          state.filters = { search: '', category: '', brand: '', product_system: '', document_type: '' };
          state.currentPage = 1;
          update();
        },
      }, 'Clear Filters') : null,
    );

    container.appendChild(h('div', { className: 'tcp-search-filters' },
      h('div', { className: 'tcp-search-box' }, searchInput),
      filtersRow,
    ));

    // Apply filters
    const filtered = filterDocuments(allDocuments, filters);
    const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
    const page = Math.min(currentPage, totalPages);
    const pageStart = (page - 1) * perPage;
    const pageDocs = filtered.slice(pageStart, pageStart + perPage);

    // Results info
    const infoText = `${filtered.length} document${filtered.length !== 1 ? 's' : ''} found`;
    const pageText = totalPages > 1 ? ` — Page ${page} of ${totalPages}` : '';
    container.appendChild(h('div', { className: 'tcp-results-info' },
      h('span', null, infoText + (hasActive ? ' (filtered)' : '') + pageText),
    ));

    // Document grid
    if (pageDocs.length > 0) {
      const grid = h('div', { className: 'tcp-documents-grid' });
      pageDocs.forEach(doc => {
        const badges = [];
        if (doc.category) badges.push(h('span', { className: 'tcp-meta-badge category' }, doc.category));
        if (doc.brand) badges.push(h('span', { className: 'tcp-meta-badge brand' }, doc.brand));
        if (doc.product_system) badges.push(h('span', { className: 'tcp-meta-badge product-system' }, doc.product_system));
        if (doc.document_type) badges.push(h('span', { className: 'tcp-meta-badge type' }, doc.document_type));

        const card = h('div', { className: 'tcp-document-card' },
          h('h3', { className: 'tcp-document-title' }, doc.title),
          doc.description ? h('p', { className: 'tcp-document-description' }, doc.description) : null,
          badges.length > 0 ? h('div', { className: 'tcp-document-meta' }, ...badges) : null,
          doc.hubspot_file_url ? h('a', {
            href: doc.hubspot_file_url,
            className: 'tcp-view-button',
            target: '_blank',
            rel: 'noopener noreferrer',
          }, 'View PDF') : null,
        );
        grid.appendChild(card);
      });
      container.appendChild(grid);
    } else {
      container.appendChild(h('div', { className: 'tcp-empty-state' },
        h('div', { className: 'tcp-empty-icon' }, '\uD83D\uDCC4'),
        h('p', { className: 'tcp-empty-message' }, 'No documents found'),
      ));
    }

    // Pagination
    if (totalPages > 1) {
      const pageNumbers = h('div', { className: 'tcp-page-numbers' });

      const addPageBtn = (num) => {
        const btn = h('button', {
          className: 'tcp-page-number' + (num === page ? ' active' : ''),
          onClick: () => { state.currentPage = num; update(); },
        }, String(num));
        pageNumbers.appendChild(btn);
      };

      const addEllipsis = () => {
        pageNumbers.appendChild(h('span', { className: 'tcp-page-ellipsis' }, '...'));
      };

      addPageBtn(1);
      let start = Math.max(2, page - 2);
      let end = Math.min(totalPages - 1, page + 2);
      if (start > 2) addEllipsis();
      for (let i = start; i <= end; i++) addPageBtn(i);
      if (end < totalPages - 1) addEllipsis();
      if (totalPages > 1) addPageBtn(totalPages);

      const prevBtn = h('button', {
        className: 'tcp-page-button',
        disabled: page === 1,
        onClick: () => { state.currentPage = page - 1; update(); },
      }, '\u2190 Previous');

      const nextBtn = h('button', {
        className: 'tcp-page-button',
        disabled: page === totalPages,
        onClick: () => { state.currentPage = page + 1; update(); },
      }, 'Next \u2192');

      container.appendChild(h('div', { className: 'tcp-pagination' }, prevBtn, pageNumbers, nextBtn));
    }
  }

  // ── Initialization ──

  function initApp() {
    const container = document.getElementById('tcp-document-library');
    if (!container) return;

    // Read config from data attributes (set by Liquid section)
    CONFIG.apiKey = container.dataset.apiKey || '';
    CONFIG.spreadsheetId = container.dataset.spreadsheetId || '';
    CONFIG.perPage = parseInt(container.dataset.perPage, 10) || 30;

    if (!CONFIG.apiKey || !CONFIG.spreadsheetId) {
      container.innerHTML = '<div class="tcp-error-state"><p>Document library is not configured. Please set the Google Sheets API Key and Spreadsheet ID in the theme section settings.</p></div>';
      return;
    }

    // Show loading
    container.innerHTML = '<div class="tcp-loading"><div class="tcp-spinner"></div><p>Loading documents...</p></div>';

    // App state
    const state = {
      allDocuments: [],
      documents: [],
      filters: {
        search: container.dataset.search || '',
        category: container.dataset.category || '',
        brand: container.dataset.brand || '',
        product_system: container.dataset.productSystem || '',
        document_type: container.dataset.documentType || '',
      },
      currentPage: 1,
      perPage: CONFIG.perPage,
    };

    // Make update function available
    window.__tcpDocsUpdate = update;

    function update() {
      renderApp(container, state);
    }

    // Try cache first, then fetch fresh in background
    const cached = getCachedDocs();
    if (cached) {
      state.allDocuments = cached;
      update();
    }

    // Always fetch fresh data (auto-sync)
    fetchAllDocuments(CONFIG.apiKey, CONFIG.spreadsheetId)
      .then(docs => {
        state.allDocuments = docs;
        setCachedDocs(docs);
        update();
      })
      .catch(err => {
        console.error('TCP Docs: Failed to load documents:', err);
        if (!cached) {
          container.innerHTML = '<div class="tcp-error-state"><p>Failed to load documents. Please check API key and spreadsheet configuration.</p></div>';
        }
      });
  }

  // Wait for DOM
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
  } else {
    initApp();
  }
})();
