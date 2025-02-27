<?php

namespace RRZE\UnivIS;

if ($data): ?>
<div class="rrze-univis">
    <?php foreach ($data as $department => $persons):
    echo '<h' . $this->atts['hstart'] . '><a name="' . $department . '">' . $department . '</a></h' . $this->atts['hstart'] . '>';
    ?>
	    <ul class="person liste-person" itemscope itemtype="http://schema.org/Person">
	        <?php
    foreach ($persons as $person):
        $name = array();
        $p = array();
        $pers = array();
        $fullname = '';
        $out = '';
        ?>
		        <li>
		        <?php
        if (!empty($person['title'])):
            $name['title'] = '<span itemprop="honorificPrefix"><abbr title="' . $person['title_long'] . '">' . $person['title'] . '</abbr></span>';
        endif;
        if (!empty($person['firstname'])):
            $p['firstname'] = '<span itemprop="givenName">' . $person['firstname'] . '</span>';
            if (!empty($person['lastname'])):
                $p['firstname'] .= ' ';
            endif;
        endif;
        if (!empty($person['lastname'])):
            $p['lastname'] = '<span itemprop="familyName">' . $person['lastname'] . '</span>';
        endif;
        if (!empty($p)):
            $n = implode(' ', $p);
            if (!empty($person['person_id'])):
                $aShortcodeParams = [
                    'show' => implode(',', $this->show),
                    'hide' => implode(',', $this->hide),
                ];
                $url = get_permalink() . 'univisid/' . $person['person_id'] . '_' . Main::custom_http_build_query($aShortcodeParams);
                $fullname .= '<a class="url" href="' . $url . '" itemprop="name">';
            endif;
            $fullname .= $n;
            if (!empty($person['person_id'])):
                $fullname .= '</a>';
            endif;
            $name['fullname'] = $fullname;
        endif;
        $pers['fullname'] = implode(' ', $name);
        if (!empty($person['atitle'])):
            $pers['atitle'] = '<span itemprop="honorificSuffix"><abbr title="' . $person['atitle'] . '">' . $person['atitle'] . '</abbr></span>';
        endif;
        if (!empty($person['locations'])) {
            // tel
            foreach ($person['locations'] as $location) {
                if (!empty($location['tel']) && in_array('telefon', $this->show) && !in_array('telefon', $this->hide)) {
                    if (in_array('call', $this->show) && !in_array('call', $this->hide)) {
                        $pers[] = '<span class="screen-reader-text">' . __('Phone number', 'rrze-univis') . ': </span><span itemprop="telephone"><a href="tel:' . $location['tel_call'] . '"> ' . $location['tel'] . '</a></span>';
                    } else {
                        $pers[] = '<span class="screen-reader-text">' . __('Phone number', 'rrze-univis') . ': </span><span itemprop="telephone">' . $location['tel'] . '</span>';
                    }
                }
            }
            // mobile
            foreach ($person['locations'] as $location) {
                if (!empty($location['mobile']) && in_array('mobile', $this->show) && !in_array('mobile', $this->hide)) {
                    if (in_array('call', $this->show) && !in_array('call', $this->hide)) {
                        $pers[] = '<span class="screen-reader-text">' . __('Mobile number', 'rrze-univis') . ': </span><span class="mobile" itemprop="telephone"><a href="tel:' . $location['mobile_call'] . '"> ' . $location['mobile'] . '</a></span>';
                    } else {
                        $pers[] = '<span class="screen-reader-text">' . __('Mobile number', 'rrze-univis') . ': </span><span class="mobile" itemprop="telephone">' . $location['mobile'] . '</span>';
                    }
                }
            }
            // fax
            foreach ($person['locations'] as $location) {
                if (!empty($location['fax']) && in_array('fax', $this->show) && !in_array('fax', $this->hide)) {
                    $pers[] = '<span class="screen-reader-text">' . __('Fax number', 'rrze-univis') . ': </span><span itemprop="faxNumber">' . $location['fax'] . '</span>';
                }
            }
            // email
            // foreach ($person['locations'] as $location) {
            //     if (!empty($location['email']) && in_array('mail', $this->show) && !in_array('mail', $this->hide)) {
            //         $pers[] = '<span class="screen-reader-text">' . __('Email', 'rrze-univis') . ': </span><span itemprop="email">' . $location['email'] . '</span>';
            //     }
            // }
            // address
            foreach ($person['locations'] as $location) {
                if (!empty($location['url']) && ((in_array('url', $this->show) && !in_array('url', $this->hide)) || ((in_array('address', $this->show) && !in_array('address', $this->hide) && !in_array('url', $this->hide))))) {
                    $pers[] = '<span class="screen-reader-text">' . __('Website', 'rrze-univis') . ': </span><a itemprop="url" href="' . $location['url'] . '">' . $location['url'] . '</a>';
                }
                if (in_array('address', $this->show) && !in_array('address', $this->hide) && (!empty($location['street']) || !empty($location['ort']) || !empty($location['office']))) {
                    if (!empty($location['street'])) {
                        $pers[] = '<span class="person-info-street" itemprop="streetAddress">' . $location['street'] . '</span>';
                    }
                    if (!empty($location['ort'])) {
                        $pers[] = '<span itemprop="addressLocality">' . $location['ort'] . '</span>';

                    }
                    if (!empty($location['office']) && !in_array('office', $this->hide)) {
                        $pers[] = __('Room', 'rrze-univis') . ' ' . $location['office'];
                    }
                }
                if (in_array('office', $this->show) && !in_array('office', $this->hide) && !in_array('address', $this->show) && (!empty($location['office']))) {
                    $pers[] = __('Room', 'rrze-univis') . ' ' . $location['office'];
                }
            }
        }
        $out = implode(', ', $pers);
        ?>
		            <span class="person-info" itemprop="name"><?php echo $out; ?></span>
		        </li>
		        <?php endforeach;?>
	    </ul>
	    <?php endforeach;?>
</div>
<?php endif;?>
