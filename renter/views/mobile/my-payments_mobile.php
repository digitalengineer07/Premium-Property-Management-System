-NoNewline

<?php if(!empty($t['id'])): ?>
                                    <a href="receipt.php?month=<?php echo urlencode(date('F Y', strtotime($t['month'].'-01'))); ?>" style="text-decoration: none; background: rgba(98, 75, 255, 0.05); border: 1px solid rgba(98, 75, 255, 0.2); border-radius: 8px; padding: 6px 10px; display: inline-flex; align-items: center; justify-content: center; color: #624BFF; cursor: pointer;"><i class='bx bx-download' style="font-size: 16px;"></i></a>
                                <?php endif; ?>
