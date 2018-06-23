require 'uri'
require 'net/http'
require 'json'

class Twikey
  # @url
	# @api_key => from operator
  # @authHeader
	def initialize(api_key,url = "https://api.twikey.com")
		@api_key = api_key
		@url = url
		@auth_time = Time.at(0)
	end
  
  def refresh_authentication()
		if @api_key
			if (Time.now - @auth_time) < 43200  #12 hour
				return true
			end
				
			@uri = URI(@url)
			@http = Net::HTTP.new(@uri.host, @uri.port)
			@http.use_ssl = @uri.port == 443
			request = Net::HTTP::Post.new(@uri.path + "/creditor")
			request.set_form_data({:apiToken => @api_key})
			response = @http.request(request)
			@auth_header = response['Authorization']
			if @auth_header != nil
				@auth_time = Time.now
				return true
			end			 
		end
		false
	end

	def invite(ct, customer)
		if self.refresh_authentication
			request = Net::HTTP::Post.new(@uri + "/creditor/prepare")
			request['Authorization'] = @auth_header
			# add the template id
			customer[:ct] = ct	
			
			request.set_form_data(customer)
			response = @http.request(request)
			case response
				when Net::HTTPSuccess then
					invite = JSON.parse(response.body)
					if invite.key?('mndtId')
						# Existing mandate 
						# mandate = Mandate.create!(billing_entity_id: customer_be.id, mandate_id: invite['mndtId'], sign_url: invite['url'], status: :pending)
					end
				else
					warn "Invite::Bad request #{response.body}"
			end			
		end
	end

	def update_feed(chunk_size=100)
		if self.refresh_authentication
			request = Net::HTTP::Get.new(@uri + "/creditor/mandate")
			request['Authorization'] = @auth_header
			request.set_form_data({
	    		chunk_size: chunk_size
			})
			response = @http.request(request)
			case response
				when Net::HTTPSuccess then
					body = JSON.parse(response.body)
					messages = body['Messages']
					messages.each do |msg|
						if  msg.key?('AmdmntRsn') || msg.key?('CxlRsn')
							mandate_id = msg['OrgnlMndtId']
						elsif msg.key?('Mndt')
							mandate_id = msg['Mndt']['MndtId']
						end
						puts "Update received for #{mandate_id}"
						# sample code
						# mandate = Mandate.find_by(mandate_id: mandate_id)
						# if !mandate.blank?
						# 	if  msg.key?('CxlRsn')
						# 		mandate.mandate_signed = nil
						# 		mandate.status = :cancelled
						# 	end
            # 
						# 	if  msg.key?('AmdmntRsn') || msg.key?('Mndt')
						# 		mandate.mandate_signed = msg['EvtTime']
						# 		mandate.status = :signed
						# 	end
						# 	mandate.save!
						# end
					end
				else
					warn "Update::Bad request #{response.body}"
			end	
		end
	end

	def new_transaction(mandate,invoice)
		if self.refresh_authentication
			if mandate
				if mandate.signed?
					request = Net::HTTP::Post.new(@uri + "/creditor/transaction")
					request['Authorization'] = @auth_header
					request.set_form_data({
					    :mndtId => mandate.mandate_id,
					    :date => invoice.invoice_date.strftime('%Y-%m-%d'),
					    :reqcolldt => invoice.due_date.strftime('%Y-%m-%d'),
					    :message => invoice.structured_reference.blank? ? invoice.invoice_number : invoice.structured_reference_str,
					    :ref => invoice.friendly_id,
					    :amount => invoice.saldo
					})
					response = @http.request(request)
					case response
						when Net::HTTPSuccess then
							return response.body
						else
							warn "NewTransaction::Bad request #{response.body}"
					end
				end
			end
		end
	end

	def retrieve_pdf(mandate)
		if self.refresh_authentication
			if mandate
				url = @uri + "/creditor/mandate/pdf"
				url.query = "mndtId=" + mandate[:mandate_id]
				request = Net::HTTP::Get.new(url)
				request['Authorization'] = @auth_header
				response = @http.request(request)
				case response
					when Net::HTTPSuccess then
						File.open( mandate[:mandate_id] + '.pdf', 'w') { |file| file.write(response.body) }
					when Net::HTTPNotFound then
						warn "PDF::Not found #{mandate[:mandate_id]}"
					else
						warn "PDF::Bad request #{response.body}"
				end
			end
		end
	end

	def transaction_status(payment)
		if self.refresh_authentication
			request = Net::HTTP::Get.new(@uri + "/creditor/transaction")
			request['Authorization'] = @auth_header
			response = @http.request(request)
			case response
				when Net::HTTPSuccess then
					body = JSON.parse(response.body)
					payments = body['Entries']
					payments.each do |p|
						# Find an invoice
						invoice = Invoice.find_invoice(p['ref'])
						if invoice
							if invoice.sent?
								#find payment by extern id
								if payment.blank?
									if p['final']
										invoice.status = p['state']
										invoice.save!
									else
										puts "Still being handled by Twikey"
									end
								end
							end
						end
					end
				else
					warn "NewTransaction::Bad request #{response.body}"
			end
		end
	end
end