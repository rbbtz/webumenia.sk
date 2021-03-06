@extends('layouts.master')

@section('title')
    @if ($title) {{ $title }} | @endif
    {{ trans('katalog.title') }} |
    @parent
@stop

@section('link')
    @include('includes.pagination_links', ['paginator' => $paginator])
@stop

@section('content')

    @formStart($form, ['attr' => ['class' => 'js-filter-form filter-form']])

    @include('frontend.catalog.form')

    @if ($untranslated)
    <section class="catalog">
        <div class="container content-section">
            <div class="row">
                @include('includes.message_untranslated')
            </div>
        </div>
    </section>
    @endif

    <section class="catalog" data-searchd-engine="{!! Config::get('app.searchd_id') !!}">
        <div class="container content-section">
            <div class="row content-section">
                <div class="col-xs-6">
                    @if (!empty($search))
                        <h4 class="inline">{{ utrans('katalog.catalog_found_artworks') }} &bdquo;{{ $search }}&ldquo; (<span data-searchd-total-hits>{{ $total }}</span>) </h4>
                    @else
                        <h4 class="inline">{{ formatNum($total) }} {{ trans('katalog.catalog_artworks') }} </h4>
                    @endif
                    @if ($paginator->count() == 0)
                        <p class="text-center">{{ utrans('katalog.catalog_no_artworks') }}</p>
                    @endif
                    @if ($hasFilters)
                        <a class="btn btn-sm btn-default btn-outline  sans" href="{{ URL::to('katalog') }}">{{ trans('general.clear_filters') }}  <i class="icon-cross"></i></a>
                    @endif
                    @if ($form['color']->vars['value'])
                        <a class="btn btn-sm btn-default btn-outline sans" href="{{ $urlWithoutColor }}" id="clear_color">{{ trans('general.clear_color') }} <span class="picked-color" style="background-color: {{ $form['color']->vars['value'] }};">&nbsp;</span> <i class="icon-cross"></i></a>
                    @endif
                </div>
                <div class="col-xs-6 text-right">
                    @formRow($form['sort_by'], ['attr' => ['class' => 'js-dropdown-select']])
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12 isotope-wrapper">
                    <div id="iso">
                        @foreach ($paginator as $item)
                            <div class="col-md-3 col-sm-4 col-xs-6 item">
                                
                                @include('components.item_image_responsive', [
                                    'item' => $item ,
                                    'limitRatio' => 3,
                                    'url' => $item->getUrl(),
                                ])
                                
                                <div class="item-title">
                                    @if ($item->has_iip)
                                        <div class="pull-right"><a href="{{ route('item.zoom', ['id' => $item->id]) }}" data-toggle="tooltip" data-placement="left" title="{{ utrans('general.item_zoom') }}"><i class="fa fa-search-plus"></i></a></div>
                                    @endif
                                    <a href="{!! $item->getUrl() !!}" {!! (!empty($search))  ?
                                    'data-searchd-result="title/'.$item->id.'" data-searchd-title="'.implode(', ', $item->authors).' - '. $item->title.'"'
                                    : '' !!}>
                                        <em>{!! implode(', ', $item->authors) !!}</em><br>
                                        <strong>{!! $item->title !!}</strong><br>
                                        <em>{!! $item->getDatingFormated() !!}</em>
                                        {{-- <br><span class="">{!! $item->gallery !!}</span> --}}
                                    </a>
                                </div>
                            </div>
                        @endforeach

                    </div>
                    <div class="col-sm-12 text-center">

                        {!! $paginator->appends(@Input::except('page'))->render() !!}
                        @if ($paginator->hasMorePages() )
                            <a id="next" href="{{ $paginator->nextPageUrl() }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="100px" height="100px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
                                    <path d="M0.492,8.459v83.427c4.124,0.212,7.409,3.497,7.622,7.622h83.357c0.22-4.265,3.719-7.664,8.036-7.664V8.571c-4.46,0-8.079-3.617-8.079-8.079H8.157C8.157,4.774,4.755,8.239,0.492,8.459z"/>
                                    <text text-anchor="middle" alignment-baseline="middle" x="50" y="50">
                                    {{ trans('katalog.catalog_show_more') }}
                                    </text>
                                </svg>
                            </a>
                            <div class="page-load-status" style="display: none">
                                <p class="infinite-scroll-request" id="infscr-loading"><i class="fa fa-refresh fa-spin fa-lg"></i></p>
                                <p class="infinite-scroll-last text-muted">{{ utrans('katalog.catalog_finished') }}</p>
                                <p class="infinite-scroll-error">{{ utrans('katalog.catalog_finished') }}</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </section>

    @formEnd($form, ['render_rest' => false])


@stop

@section('javascript')

    {!! Html::script('js/bootstrap-slider.min.js') !!}
    {!! Html::script('js/selectize.min.js') !!}
    {!! Html::script('js/readmore.min.js') !!}
    {!! Html::script('js/jquery.dropdown-select.js') !!}
    <script src="{!! asset_timed('js/scroll-frame.js') !!}"></script>

    <script type="text/javascript">

        // start with isotype even before document is ready
        $('.isotope-wrapper').each(function(){
            var $container = $('#iso', this);
            spravGrid($container);
        });

        $(document).ready(function(){

            $("form").submit(function()
            {
                $(this).find('input[name], select[name]').each(function(){
                    if (!$(this).val()){
                        $(this).data('name', $(this).attr('name'));
                        $(this).removeAttr('name');
                    }
                });
            });

            $('#years-range').on('change', function(event) {
                $(this).closest('form').submit();
            });

            $('.js-filter-form').each(function () {
                var $filterForm = $(this);
                $filterForm.find('select, input:not([type=hidden])').change(function () {
                    $filterForm.submit();
                });
            });

            $('.js-dropdown-select').dropdownSelect();

            $(".js-custom-select").selectize({
                plugins: ['remove_button'],
                // maxItems: 2,
                maxItems: 1,
                placeholder: $(this).attr('data-placeholder'),
                mode: 'multi',
                render: {
                    item: function(data, escape) {
                        return '<div class="selected-item">'  + '<span class="color">'+this.settings.placeholder+': </span>' +  data.text.replace(/\(.*?\)/g, "") + '</div>';
                    }
                }
            });

            $(".js-color-clear").click(function(e) {
                e.preventDefault();
                $(this).closest('label').find('input').val('');
                $(this).closest('form').submit();
            });

            var $container = $('#iso');

            $(window).resize(function() {
                spravGrid($container);
            });

            $container.infiniteScroll({
                path: '#next',
                append: '.item',
                outlayer: $container.data('isotope'),
                loadOnScroll: false, // Start with scroll disabled
                status: '.page-load-status'
            });

            $container.on( 'request.infiniteScroll', function( event, path ) {
                $('.infinite-scroll-request').addClass('animated fadeInUp faster');
            });

            $('#next').on('click', function(event) {
                $container.infiniteScroll('loadNextPage');
                $container.infiniteScroll('option', {
                    loadOnScroll: true,
                });

                $(this).fadeOut();
                event.preventDefault();
            });

            // fix artwork detail on iOS https://github.com/artsy/scroll-frame/issues/30
            if (!isMobileSafari() && !isIE()) {
                scrollFrame('.item a');
            }
        });

    </script>
@stop
