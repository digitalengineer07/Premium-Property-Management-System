<?php
$file = 'c:/xampp/htdocs/renter-system/renter/views/mobile/my-payments_mobile.php';
$content = file_get_contents($file);

$target = <<<EOT
    </div>

    <!-- Notice & Pay All Button -->
EOT;

$replacement = <<<'EOT'
    </div>

    <!-- Pagination Controls -->
    <div id="mPaymentsPagination" style="display: flex; justify-content: center; align-items: center; padding: 0 0 24px 0; gap: 24px;">
        <button id="mPaymentsPrev" style="background: var(--white); border: 1px solid var(--border); border-radius: 12px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-dark); box-shadow: 0 2px 8px rgba(0,0,0,0.02);"><i class='bx bx-chevron-left' style="font-size: 20px;"></i></button>
        <span id="mPaymentsPageNum" style="font-size: 16px; font-weight: 800; color: #624BFF;">1</span>
        <button id="mPaymentsNext" style="background: var(--white); border: 1px solid var(--border); border-radius: 12px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-dark); box-shadow: 0 2px 8px rgba(0,0,0,0.02);"><i class='bx bx-chevron-right' style="font-size: 20px;"></i></button>
    </div>

    <script>
    (function() {
        const mobileWrapper = document.querySelector('.mobile-view-wrapper');
        if (!mobileWrapper) return;
        
        const tabs = mobileWrapper.querySelectorAll('.m-ptab');
        const select = mobileWrapper.querySelector('select');
        const items = Array.from(mobileWrapper.querySelectorAll('.m-pay-card-item'));
        
        const prevBtn = mobileWrapper.querySelector('#mPaymentsPrev');
        const nextBtn = mobileWrapper.querySelector('#mPaymentsNext');
        const pageNum = mobileWrapper.querySelector('#mPaymentsPageNum');
        
        let currentType = 'all';
        let currentYear = 'all';
        let currentPage = 1;
        const itemsPerPage = 6;
        
        function applyFiltersAndPagination() {
            const visibleItems = items.filter(it => {
                const type = it.getAttribute('data-type');
                const year = it.getAttribute('data-year');
                const typeMatch = (currentType === 'all' || type === currentType);
                const yearMatch = (currentYear === 'all' || year === currentYear || currentYear.includes('all') || currentYear.includes('All'));
                return typeMatch && yearMatch;
            });
            
            const totalPages = Math.ceil(visibleItems.length / itemsPerPage);
            if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;
            if (currentPage === 0 && totalPages > 0) currentPage = 1;
            
            items.forEach(it => it.style.display = 'none');
            
            const start = (currentPage - 1) * itemsPerPage;
            const pageItems = visibleItems.slice(start, start + itemsPerPage);
            
            pageItems.forEach((it, idx) => {
                it.style.display = 'flex';
                if (idx === pageItems.length - 1) {
                    it.style.borderBottom = 'none';
                } else {
                    it.style.borderBottom = '1px solid var(--border)';
                }
            });
            
            if (pageNum) pageNum.textContent = currentPage;
            if (prevBtn) {
                prevBtn.style.opacity = currentPage <= 1 ? '0.4' : '1';
                prevBtn.style.pointerEvents = currentPage <= 1 ? 'none' : 'auto';
            }
            if (nextBtn) {
                nextBtn.style.opacity = currentPage >= totalPages || totalPages === 0 ? '0.4' : '1';
                nextBtn.style.pointerEvents = currentPage >= totalPages || totalPages === 0 ? 'none' : 'auto';
            }
            
            const paginationContainer = mobileWrapper.querySelector('#mPaymentsPagination');
            if (paginationContainer) {
                paginationContainer.style.display = totalPages <= 1 ? 'none' : 'flex';
            }
        }
        
        tabs.forEach(btn => {
            btn.removeAttribute('onclick');
            btn.addEventListener('click', (e) => {
                tabs.forEach(b => b.classList.remove('active'));
                e.currentTarget.classList.add('active');
                
                const text = e.currentTarget.textContent.trim().toLowerCase();
                if(text.includes('all')) currentType = 'all';
                else if(text.includes('rent')) currentType = 'rent';
                else if(text.includes('electric')) currentType = 'electricity';
                else currentType = 'other';
                
                currentPage = 1;
                applyFiltersAndPagination();
            });
        });
        
        if (select) {
            select.removeAttribute('onchange');
            select.addEventListener('change', (e) => {
                currentYear = e.target.value;
                currentPage = 1;
                applyFiltersAndPagination();
            });
        }
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    applyFiltersAndPagination();
                }
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                const visibleItems = items.filter(it => {
                    const type = it.getAttribute('data-type');
                    const year = it.getAttribute('data-year');
                    const typeMatch = (currentType === 'all' || type === currentType);
                    const yearMatch = (currentYear === 'all' || year === currentYear || currentYear.includes('all') || currentYear.includes('All'));
                    return typeMatch && yearMatch;
                });
                const totalPages = Math.ceil(visibleItems.length / itemsPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                    applyFiltersAndPagination();
                }
            });
        }
        
        applyFiltersAndPagination();
    })();
    </script>

    <!-- Notice & Pay All Button -->
EOT;

if (strpos($content, $target) !== false) {
    $content = str_replace($target, $replacement, $content);
    file_put_contents($file, $content);
    echo "Success";
} else {
    echo "Target not found";
}
?>
