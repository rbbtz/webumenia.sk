<form class="khb-newsletter-signup-form js-newsletter-signup-form" method="post" action="{{ route('newsletter.signup') }}">
    <div class="form-group row">
        <label for="newsletterEmail" class="col-sm-2 col-lg-3 col-form-label border-0">Newsletter</label>
        <div class="col-sm-10 col-lg-9 border-0">
            <input name="email" type="email" class="form-control" id="newsletterEmail" placeholder="Email" required="required">
            <input type="submit" class="invisible position-absolute">
        </div>
    </div>
</form>
