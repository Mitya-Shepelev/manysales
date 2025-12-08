<div class="modal fade" id="modalForVkPixel" tabindex="-1" aria-labelledby="modalForVkPixel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered max-w-655px">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                    data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body px-20 py-0 mb-30">
                <div class="swiper instruction-carousel pb-3">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <div class="swiper-slide">
                                <div class="">
                                    <div class="d-flex justify-content-center mb-5">
                                        <img height="60"
                                             src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/vk.svg') }}"
                                             loading="lazy" alt="">
                                    </div>
                                    <div class="text-dark mb-3">
                                        <h3 class="lh-base">
                                            {{ translate('how_to_get_vk_pixel_id') }}
                                        </h3>
                                        <p class="opacity-75">
                                            {{ translate('to_get_your_vk_pixel_id_log_into_your_vk_ads_account') }}
                                            {{ translate('go_to_sites_section_and_find_pixels') }}
                                            {{ translate('create_new_pixel_or_select_existing') }}
                                            {{ translate('copy_the_numeric_pixel_id') }}
                                        </p>
                                    </div>

                                    <div class="text-dark mb-3">
                                        <h3 class="lh-base">
                                            {{ translate('where_to_use_vk_pixel_id') }}
                                        </h3>
                                        <p class="opacity-75">
                                            {{ translate('find_the_marketing_tools_feature_from_your_admin_panel_and_follow_the_instructions:') }}
                                        </p>
                                        <ol class="d-flex flex-column gap-2 opacity-75">
                                            <li>
                                                {{ translate('navigate_to_vk_pixel_section') }}
                                            </li>
                                            <li>
                                                {{ translate('turn_on_the_toggle_button.') }}
                                            </li>
                                            <li>
                                                {{ translate('paste_your_meta_pixel_id_into_the_input_box_and_click_submit.') }}
                                            </li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
