<?php
// partials/ads_table.php
if(mysqli_num_rows($users_result) > 0) {
    ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>عنوان</th>
                    <th>نمایش</th>
                    <th>مشتری/آژانس</th>
                    <th>تاریخ ایجاد</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while($users_row = mysqli_fetch_array($users_result)) {
                    $ad_id = $users_row['id'];
                    ?>
                    <tr>
                        <td><?php echo $ad_id; ?></td>
                        <td><?php echo htmlspecialchars($users_row['title'] ?? ''); ?></td>
                        <td>
                            <?php if($users_row['ad_type'] === 'banner'): ?>
                                <div class="horizontal-scroll-container">
                                    <div class="scroll-item">
                                        <img src="<?php echo htmlspecialchars($users_row['content'] ?? ''); ?>" alt="بنر تبلیغاتی">
                                    </div>
                                </div>
                            <?php elseif($users_row['ad_type'] === 'fullscreen'): ?>
                                <div class="fullscreen-preview">
                                    <i class="fa fa-desktop"></i>
                                </div>
                            <?php else: ?>
                                <div class="scroll-item">
                                    <div class="text-preview">
                                        <i class="fa fa-ad"></i>
                                        <div><?php echo substr(htmlspecialchars($users_row['content'] ?? ''), 0, 50); ?>...</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($users_row['agent_name'] ?? 'بدون مشتری'); ?></td>
                        <td><?php echo jdate('Y/m/d', strtotime($users_row['created_at'])); ?></td>
                        <td>
                            <span class="badge <?php echo ($users_row['status'] == 1) ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo ($users_row['status'] == 1) ? 'فعال' : 'غیرفعال'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="add_ad.php?ad_id=<?php echo $ad_id; ?>" class="btn btn-primary btn-sm" title="ویرایش">
                                <i class="fa fa-edit"></i>
                            </a>
                            <a href="ad.php?ad_id=<?php echo $ad_id; ?>" class="btn btn-danger btn-sm" title="حذف" onclick="return confirm('آیا از حذف این تبلیغ مطمئن هستید؟')">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
} else {
    echo '<div class="alert alert-info">هیچ تبلیغی یافت نشد</div>';
}
?>