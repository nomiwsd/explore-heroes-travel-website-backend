<div class="bravo-contact-block">
    <div class="container">
        <div class="row section">
            <div class="col-md-12">
                <div role="form" class="form_wrapper" lang="en-US" dir="ltr">
                    <form method="post" action="{{ route("tailor-made-tour.storeTailorMadeTour") }}"
                          class="bravo-contact-block-form" style="{{ app()->getLocale() == "ar"? "text-align:right;direction: rtl":"" }}">
                        {{csrf_field()}}
                        <div style="display: none;">
                            <input type="hidden" name="g-recaptcha-response" value="">
                        </div>
                        <div class="contact-form">
                            <div class="contact-header">
                                <h1>{{ __('We would love to hear from you') }}</h1>
                                <h2>{{ __('Send us a your information our team will be get back to you as soon as possible') }}</h2>
                            </div>
                            @include('admin.message')
                            <div class="col-contact-form">
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <label class="control-label">{{ __('Salutation') }}</label>
                                        <div>
                                            <select class="form-control" name="salutation" id="salutation" required="">
                                                <option value="">- {{ __('Select Salutation') }} -</option>
                                                <option value="Mr">{{ __('Mr') }}</option>
                                                <option value="Mrs">{{ __('Mrs') }}</option>
                                                <option value="Miss">{{ __('Miss') }}</option>
                                                <option value="Ms">{{ __('Ms') }}</option>
                                            </select>
                                            <label id="a"></label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="control-label">{{ __('First Name') }}</label>
                                        <div>
                                            <input type="text" class="form-control" id="first_name" name="first_name"
                                                   value=""
                                                   required="">
                                            <label id="b"></label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="control-label">{{ __('Last Name') }}</label>
                                        <div>
                                            <input type="text" class="form-control" id="last_name" name="last_name"
                                                   value=""
                                                   required="">
                                            <label id="c"></label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="control-label">{{ __('Telephone') }}</label>
                                        <div>
                                            <input type="text" class="form-control" id="phone" name="phone" value=""
                                                   required="">
                                            <label id="d"></label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="control-label">{{ __('Email') }}</label>
                                        <div>
                                            <input type="email" class="form-control" id="email" name="email" value=""
                                                   required="">
                                            <label id="e"></label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="control-label">{{ __('Country') }}</label>
                                        <div>
                                            <select name="country" class="form-control" id="country" required="">
                                                <option value="">- {{ __('Select Country') }} -</option>
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country }}" {{ old('country') == $country ? 'selected' : '' }}>
                                                        {{ $country }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <label id="f"></label>
                                        </div>
                                    </div>

                                    <div class="col-md-12 form-group">
                                        <label class="control-label"><strong>{{ __('How many people in your group') }},</strong></label>
                                    </div>
                                    <br>
                                    <div class="form-group col-md-3">
                                        <label class="control-label">{{ __('Number of adults in your group') }},</label>
                                    </div>
                                    <div class="form-group col-md-9">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>{{ __('13 to 17 years') }}</label>
                                                <div class="input-group">
                                                    <span class="input-group-btn">
                                                        <button
                                                                type="button"
                                                                class="btn btn-default">-</button>
                                                    </span>
                                                    <input
                                                            id="age_13_17" name="age_13_17" class="form-control"
                                                            type="text"
                                                            value="0" min="0" readonly style="text-align: center;">
                                                    <span
                                                            class="input-group-btn">
                                                        <button type="button"
                                                                class="btn btn-default">+</button>
                                                    </span>
                                                </div>
                                                <br>
                                                <label>{{ __('18 - 25 years') }}</label>
                                                <div class="input-group"><span class="input-group-btn"><button
                                                                type="button"
                                                                class="btn btn-default">-</button></span><input
                                                            id="age_18_25" name="age_18_25" class="form-control"
                                                            type="text"
                                                            value="0" min="0" readonly style="text-align: center;"><span
                                                            class="input-group-btn"><button type="button"
                                                                                            class="btn btn-default">+</button></span>
                                                </div>
                                                <br>
                                                <label>{{ __('26 to 35 years') }}</label>
                                                <div class="input-group"><span class="input-group-btn"><button
                                                                type="button"
                                                                class="btn btn-default">-</button></span><input
                                                            id="age_26_35" name="age_26_35" class="form-control"
                                                            type="text"
                                                            value="0" min="0" readonly style="text-align: center;"><span
                                                            class="input-group-btn"><button type="button"
                                                                                            class="btn btn-default">+</button></span>
                                                </div>
                                                <br>
                                                <label>{{ __('36 to 45 years') }}</label>
                                                <div class="input-group"><span class="input-group-btn"><button
                                                                type="button"
                                                                class="btn btn-default">-</button></span><input
                                                            id="age_36_45" name="age_36_45" class="form-control"
                                                            type="text"
                                                            value="0" min="0" readonly style="text-align: center;"><span
                                                            class="input-group-btn"><button type="button"
                                                                                            class="btn btn-default">+</button></span>
                                                </div>
                                                <br>
                                            </div>
                                            <div class="col-sm-6">
                                                <label>{{ __('46 to 55 years') }}</label>
                                                <div class="input-group"><span class="input-group-btn"><button
                                                                type="button"
                                                                class="btn btn-default">-</button></span><input
                                                            id="age_46_55" name="age_46_55" class="form-control"
                                                            type="text"
                                                            value="0" min="0" readonly style="text-align: center;"><span
                                                            class="input-group-btn"><button type="button"
                                                                                            class="btn btn-default">+</button></span>
                                                </div>
                                                <br>
                                                <label>{{ __('56 to 69 years') }}</label>
                                                <div class="input-group"><span class="input-group-btn"><button
                                                                type="button"
                                                                class="btn btn-default">-</button></span><input
                                                            id="age_56_69" name="age_56_69" class="form-control"
                                                            type="text"
                                                            value="0" min="0" readonly style="text-align: center;"><span
                                                            class="input-group-btn"><button type="button"
                                                                                            class="btn btn-default">+</button></span>
                                                </div>
                                                <br>
                                                <label>{{ __('70 years and above') }}</label>
                                                <div class="input-group"><span class="input-group-btn"><button
                                                                type="button"
                                                                class="btn btn-default">-</button></span><input
                                                            id="age_70_above" name="age_70_above" class="form-control"
                                                            type="text"
                                                            value="0" min="1" readonly style="text-align: center;"><span
                                                            class="input-group-btn"><button type="button"
                                                                                            class="btn btn-default">+</button></span>
                                                </div>
                                                <br>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label class="control-label">{{ __('Number of children in your group') }},</label>
                                    </div>
                                    <div class="form-group col-md-9">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>{{ __('Below 2 years') }}</label>
                                                <div class="input-group"><span class="input-group-btn"><button
                                                                type="button"
                                                                class="btn btn-default">-</button></span><input
                                                            id="age_below_2" name="age_below_2" class="form-control"
                                                            type="text"
                                                            value="0" min="0" readonly style="text-align: center;"><span
                                                            class="input-group-btn"><button type="button"
                                                                                            class="btn btn-default">+</button></span>
                                                </div>
                                                <br>
                                                <label>{{ __('3 to 7 years') }}</label>
                                                <div class="input-group"><span class="input-group-btn"><button
                                                                type="button"
                                                                class="btn btn-default">-</button></span><input
                                                            id="age_3_7" name="age_3_7" class="form-control" type="text"
                                                            value="0" min="0" readonly style="text-align: center;"><span
                                                            class="input-group-btn"><button type="button"
                                                                                            class="btn btn-default">+</button></span>
                                                </div>
                                                <br>
                                            </div>
                                            <div class="col-sm-6">
                                                <label>{{ __('8 to 12 years') }}</label>
                                                <div class="input-group"><span class="input-group-btn"><button
                                                                type="button"
                                                                class="btn btn-default">-</button></span><input
                                                            id="age_8_12" name="age_8_12" class="form-control"
                                                            type="text"
                                                            value="0" min="0" readonly style="text-align: center;"><span
                                                            class="input-group-btn"><button type="button"
                                                                                            class="btn btn-default">+</button></span>
                                                </div>
                                                <br>
                                            </div>

                                        </div>
                                    </div>
                                    <script>
                                        document.addEventListener("DOMContentLoaded", function () {
                                            const buttons = document.querySelectorAll('.input-group-btn button');

                                            buttons.forEach(button => {
                                                button.addEventListener('click', function () {
                                                    const input = this.closest('.input-group').querySelector('input');
                                                    let value = parseInt(input.value) || 0;
                                                    const min = parseInt(input.getAttribute('min')) || 0;

                                                    if (this.textContent.trim() === "+") {
                                                        value++;
                                                    } else {
                                                        value = Math.max(min, value - 1);
                                                    }

                                                    input.value = value;
                                                });
                                            });
                                        });
                                    </script>
                                    <div class="form-group col-md-3">
                                        <label class="control-label">{{ __('Your interests') }}:</label>
                                    </div>
                                    <div class="form-group col-md-9">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input name="interests[]" type="checkbox" id="interest1"
                                                       value="Beach">
                                                {{ __('Beach') }}<br>
                                                <input name="interests[]" type="checkbox" id="interest2"
                                                       value="History">
                                                {{ __('History') }}<br>
                                                <input name="interests[]" type="checkbox" id="interest3"
                                                       value="Culture">
                                                {{ __('Culture') }}<br>
                                                <input name="interests[]" type="checkbox" id="interest4"
                                                       value="Highlights">
                                                {{ __('Highlights') }}<br>
                                                <input name="interests[]" type="checkbox" id="interest5"
                                                       value="Adventures">
                                                {{ __('Adventures') }} <br>
                                                <input name="interests[]" type="checkbox" id="interest6"
                                                       value="Wildlife ">
                                                {{ __('Wildlife') }} <br>
                                                <input name="interests[]" type="checkbox" id="interest7"
                                                       value="Bird watching">
                                                {{ __('Bird watching') }}<br>
                                            </div>
                                            <div class="col-md-6">
                                                <input name="interests[]" type="checkbox" id="interest8"
                                                       value="Off the beaten">
                                                {{ __('Off the beaten') }}<br>
                                                <input name="interests[]" type="checkbox" id="interest9"
                                                       value="Food and Drinks">
                                                {{ __('Food &amp; Drinks') }}<br>
                                                <input name="interests[]" type="checkbox" id="interest10"
                                                       value="Walking and Trekking">
                                                {{ __('Walking &amp; Trekking') }} <br>
                                                <input name="interests[]" type="checkbox" id="interest11"
                                                       value="Cycling">
                                                {{ __('Cycling') }} <br>
                                                <input name="interests[]" type="checkbox" id="interest12"
                                                       value="Interact with locals">
                                                {{ __('Interact with locals') }} <br>
                                                <input name="interests[]" type="checkbox" id="interest13"
                                                       value="I am on Honeymoon">
                                                {{ __('I am on Honeymoon') }} <br>
                                                <input name="interests[]" type="checkbox" id="interest14"
                                                       value="Train journeys">
                                                {{ __('Train journeys') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label class="control-label">{{ __('Type of accommodation you prefer') }}</label>
                                    </div>
                                    <div class="form-group col-md-9">
                                        <div>
                                            <select name="type_of_accommodation" id="type_of_accommodation"
                                                    class="form-control" required="">
                                                <option value="">-- {{ __('Please Select') }} --</option>
                                                <option value="Budget hotels">{{ __('Budget hotels') }}</option>
                                                <option value="Small Hotels with a Character">{{ __('Small Hotels with a Character') }}
                                                </option>
                                                <option value="2 to 3 star Moderate Hotels">{{ __('2 to 3 star Moderate Hotels') }}
                                                </option>
                                                <option value="4 to 5 Star Deluxe Hotels">{{ __('4 to 5 Star Deluxe Hotels') }}
                                                </option>
                                                <option value="Luxury Boutique Hotels">{{ __('Luxury Boutique Hotels') }}</option>
                                                <option value="Mixture">{{ __('Mixture') }}</option>
                                            </select>
                                            <label id="g"></label>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label class="control-label">{{ __('What is your budget per person for the trip? (Excluding flights)') }},</label>
                                    </div>
                                    <div class="form-group col-md-9">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <select class="form-control" name="budget_currency" id="budget_currency"
                                                        required="">
                                                    <option value="">- {{ __('Select Currency') }} -</option>
                                                    <option value="USD">USD</option>
                                                    <option value="GBP">GBP</option>
                                                    <option value="Euro">Euro</option>
                                                </select>
                                            </div>
                                            <div class="col-sm-6">
                                                <select class="form-control" name="budget_per_person"
                                                        id="budget_per_person"
                                                        required="">
                                                    <option value="">- {{ __('Select Amount') }} -</option>
                                                    @php
                                                        for ($i = 250; $i <= 30000; $i += 250) {
                                                            echo "<option value=\"$i\">$i</option>";
                                                        }
                                                    @endphp
                                                </select>

                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label class="control-label">{{ __('When would you like to go') }}?</label>
                                    </div>
                                    <div class="form-group col-md-9">

                                        <div>
                                            <label>
                                                <input type="radio" name="when_to_go" value="known_exact_date">
                                                {{ __('I know the exact dates') }}</label>
                                            <br>
                                            <div class="known_exact_date box" style="display: none">
                                                <label style="margin-top: 15px;">&nbsp;</label>
                                                {{ __('The date I want my trip to start is') }}:
                                                <input type="text" id="trip_date" name="trip_date"
                                                       class="form-control date-picker">
                                                <label style="margin-top: 15px;">&nbsp;</label>
                                                {{ __('Number of Nights') }}:
                                                <input name="no_of_nights_known" id="no_of_nights_known" type="number"
                                                       min="0" class="form-control"
                                                       placeholder="0">
                                                <label>&nbsp;</label>
                                            </div>
                                            <label>
                                                <input type="radio" name="when_to_go" value="roughlyIdea">
                                                {{ __('I have a rough idea') }} </label>
                                            <br>
                                            <div class="roughlyIdea box" style="display: none">
                                                <label style="margin-top: 15px;">&nbsp;</label>
                                                {{ __('The month I would like to travel in is') }}:
                                                <select name="roughly_month" id="roughly_month" class="form-control">
                                                    <option value="">-{{ __('Select One') }}-</option>
                                                    <option value="January">{{ __('January') }}</option>
                                                    <option value="February">{{ __('February') }}</option>
                                                    <option value="March">{{ __('March') }}</option>
                                                    <option value="April">{{ __('April') }}</option>
                                                    <option value="May">{{ __('May') }}</option>
                                                    <option value="June">{{ __('June') }}</option>
                                                    <option value="July">{{ __('July') }}</option>
                                                    <option value="August">{{ __('August') }}</option>
                                                    <option value="September">{{ __('September') }}</option>
                                                    <option value="October">{{ __('October') }}</option>
                                                    <option value="November">{{ __('November') }}</option>
                                                    <option value="December">{{ __('December') }}</option>
                                                </select>
                                                <label style="margin-top: 15px;">&nbsp;</label>
                                                and the year is :
                                                <select name="roughly_year" id="roughly_year" class="form-control">
                                                    <option value="">- {{ __('Select One') }} -</option>
                                                    <option value="2023">2023</option>
                                                    <option value="2024">2024</option>
                                                    <option value="2025">2025</option>
                                                    <option value="2026">2026</option>
                                                    <option value="2027">2027</option>
                                                    <option value="2028">2028</option>
                                                    <option value="2029">2029</option>
                                                    <option value="2030">2030</option>
                                                </select>
                                                <label>&nbsp;</label>
                                                <label style="margin-top: 15px;">&nbsp;</label>
                                                {{ __('Number of Nights') }}:
                                                <input name="no_of_nights_unknown" id="no_of_nights_unknown"
                                                       type="number" min="0" class="form-control"
                                                       placeholder="0" value="days">
                                                <label>&nbsp;</label>
                                            </div>
                                            <label>
                                                <input type="radio" name="when_to_go" value="notSure">
                                                {{ __('I am not sure') }}</label>
                                            <br>
                                            <div class="notSure box" style="display: none">
                                                <label style="margin-top: 15px;">&nbsp;</label>
                                                {{ __('How long is your trip (Number of Days)') }}?
                                                <input name="long" id="long" type="number" min="0" class="form-control"
                                                       placeholder="0" value="days">
                                                <label>&nbsp;</label>
                                            </div>
                                            <label>&nbsp;</label>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label class="control-label">{{ __('Any other requirements or briefly explain your expectations') }}.</label>
                                        <div>
                                            <textarea class="form-control" id="comments" name="comments"
                                                      rows="4"></textarea>
                                        </div>
                                    </div>

                                    <div class="form-group col-md-12">
                                        {{recaptcha_field('contact')}}
                                    </div>
                                </div>
                                <button class="submit btn btn-primary " type="submit">
                                    {{ __('SEND MESSAGE') }}
                                    <i class="fa fa-spinner fa-pulse fa-fw"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-mess"></div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>