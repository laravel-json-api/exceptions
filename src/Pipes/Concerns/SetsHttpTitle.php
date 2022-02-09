<?php
/*
 * Copyright 2022 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Exceptions\Pipes\Concerns;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Response;

trait SetsHttpTitle
{

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @param int|null $status
     * @return string|null
     */
    private function getTitle(?int $status): ?string
    {
        if ($status && isset(Response::$statusTexts[$status])) {
            return $this->translator->get(Response::$statusTexts[$status]);
        }

        return null;
    }
}
