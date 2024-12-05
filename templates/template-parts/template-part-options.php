<?php

$option1 = get_option( 'option1', '54410559-1d01-4ff8-b829-10f0f36bb19b' );
$option2 = get_option( 'option2', 'd3b50eac-ab88-4eb1-97e4-78f192b25bd8' );

?>

<h4 class="common-title">Options</h4>

<div class="options-wrapper">
    <div class="common-input-group">
        <label for="option1">Employee ID</label>
        <input type="text" class="common-form-input" name="option1" id="option1" placeholder="Option1"
            value="<?= $option1 ?>">
    </div>
    <div class="common-input-group mt-20">
        <label for="option2">Center ID</label>
        <input type="text" class="common-form-input" name="option2" id="option2" placeholder="Option2"
            value="<?= $option2 ?>">
    </div>

    <button type="button" class="save-btn mt-20 button-flex" id="save_options">
        <span>Save</span>
        <span class="spinner-loader-wrapper"></span>
    </button>
</div>